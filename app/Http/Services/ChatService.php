<?php

namespace App\Http\Services;

use Auth;
use App\Models\MattermostUser;
use App\Models\Channel;
use App\Models\User;
use App\Models\ChannelMember;
use App\Models\Setting;
use App\Models\Bounty;
use App\Models\Session;
use App\Models\BountyClaimRequest;
use App\Models\SessionSystemMessage;
use App\Models\UserSetting;
use Mattermost;
use Carbon\Carbon;
use App\Events\BlockChannelUpdated;
use App\Events\MuteChannelUpdated;
use App\Events\NewPostForOppositeUser;
use App\Events\NewPostForUser;
use App\Consts;
use App\Utils;
use App\Utils\ChatUtils;
use DB;
use App\Http\Services\UserService;
use App\Utils\BigNumber;
use App\Exceptions\Reports\NotEnoughBalanceToChatException;
use App\Exceptions\Reports\ChannelBlockedException;
use App\Exceptions\Reports\UserNotExitstException;
use App\Exceptions\Reports\AccountNotActivedException;
use App\Http\Services\FirebaseService;
use Aws;

class ChatService
{
    const LIMITATION_ITEMS = 5; // 5 items

    public function __construct()
    {
        $this->fcmService = new FirebaseService();
    }

    public function getUserChatSessionList($params)
    {
        $userId = Auth::id();

        $sessions = $this->getUserSessionList($userId);
        // $bounties = $this->getUserBountyList($userId);
        $bounties = null;

        return [
            Consts::OBJECT_TYPE_SESSION => $sessions,
            Consts::OBJECT_TYPE_BOUNTY => $bounties
        ];
    }

    private function getUserSessionList($userId)
    {
        return Session::with(['gamelancerInfo', 'claimerInfo', 'channel'])
            ->where(function ($query) use ($userId) {
                $query->where('gamelancer_id', $userId)
                    ->orWhere('claimer_id', $userId);
            })
            ->whereIn('status', [Consts::SESSION_STATUS_BOOKED, Consts::SESSION_STATUS_ACCEPTED])
            ->get()
            ->map(function ($item, $key) use ($userId) {
                $item->chat_channel = $this->getChannelById($item->channel->mattermost_channel_id, $userId);
                return $item;
            });
    }

    private function getUserBountyList($userId)
    {
        return BountyClaimRequest::with(['bounty', 'claimerInfo', 'channel'])
            ->where(function ($query) use ($userId) {
                $query->where('gamelancer_id', $userId)
                    ->orWhereHas('bounty', function ($query2) use ($userId) {
                        $query2->where('user_id', $userId);
                    });
            })
            ->where('status', Consts::CLAIM_BOUNTY_REQUEST_STATUS_PENDING)
            ->whereHas('bounty', function ($query) use ($userId) {
                $query->where('status', Consts::BOUNTY_STATUS_CREATED);
            })
            ->get()
            ->map(function ($item, $key) use ($userId) {
                $item->chat_channel = $this->getChannelById($item->channel->mattermost_channel_id, $userId);
                return $item;
            });
    }

    public function getUserSessionDetail($sessionId, $userId)
    {
        return Session::with(['gamelancerInfo', 'claimerInfo'])
            ->where('id', $sessionId)
            ->first();
    }

    public function getUserBountyDetail($bountyClaimId, $userId)
    {
        return BountyClaimRequest::with(['bounty', 'claimerInfo'])
            ->where('id', $bountyClaimId)
            ->first();
    }

    public function getSystemLogs($channelId)
    {
        $systemLogs = [];
        SessionSystemMessage::where('is_processed', Consts::FALSE)
            ->where('channel_id', $channelId)
            ->orderBy('started_event', 'desc')
            ->get()
            ->each(function ($item, $key) use (&$systemLogs) {
                if (($item->object_type === Consts::OBJECT_TYPE_BOUNTY && $item->data->bounty->status !== Consts::BOUNTY_STATUS_COMPLETED)
                    || ($item->object_type === Consts::OBJECT_TYPE_SESSION && $item->data->status !== Consts::SESSION_STATUS_COMPLETED && $item->data->status !== Consts::SESSION_STATUS_STOPPED)) {
                    $systemLogs[] = $item;
                }
            });
        return $systemLogs;
    }

    public function createDirectMessageChannel($oppositeUserId)
    {
        $this->validate($oppositeUserId);
        $mattermostUserId = $this->getMattermostUserId(Auth::id());

        $oppositeUser = User::find($oppositeUserId);
        $oppositeMattermostUserId = $oppositeUser->mattermostUser->mattermost_user_id;

        $channelExists = Channel::getChatChannel($oppositeUserId);
        if ($channelExists) {
            $channelExists->channel_id = $channelExists->mattermost_channel_id;

            $this->performFirehosePut($channelExists, 'Connect box chat');

            return $this->getChannelById($channelExists->channel_id, Auth::id());
        }

        $mattermostChannel = Mattermost::createDirectMessageChannel($mattermostUserId, $oppositeMattermostUserId);

        $channelTag = Channel::buildChannelTag([Auth::id(), $oppositeUserId]);
        $channel = Channel::create([
            'mattermost_channel_id' => $mattermostChannel->id,
            'channel_tag' => $channelTag
        ]);

        ChannelMember::insert([
            [
                'channel_id' => $channel->id,
                'user_id' => Auth::id()
            ],
            [
                'channel_id' => $channel->id,
                'user_id' => $oppositeUserId
            ]
        ]);

        // Update channle in cache.
        ChatUtils::addNewChannel(Auth::user(), $mattermostChannel);
        ChatUtils::addNewChannel($oppositeUser, $mattermostChannel);

        $channel->channel_id = $channel->mattermost_channel_id;

        $this->performFirehosePut($channel, 'Create box chat');

        return $this->getChannelById($channel->channel_id, Auth::id());
    }

    private function validate($oppositeUserId)
    {
        $oppositeUser = User::where('id', $oppositeUserId)->first();
        if (!$oppositeUser->email_verified && !$oppositeUser->phone_verified) {
            throw new AccountNotActivedException('exceptions.user_not_activated');
        }

        $chatSetting = UserSetting::find($oppositeUserId);

        if (Auth::user()->isGamelancer) {
            return;
        }

        if (Channel::getChatChannel($oppositeUserId)) {
            return;
        }

        if ($chatSetting->public_chat) {
            return;
        }

        $userService = new UserService;
        $userBalance = (object)$userService->getUserBalances(Auth::id());

        if (BigNumber::new($userBalance->coin)->sub(Consts::MIN_COIN_TO_CHAT)->isNegative()) {
            throw new NotEnoughBalanceToChatException();
        }
    }

    public function createPost($posts)
    {
        $this->validateChannelBlocked($posts['channel_id']);

        $userId = array_get($posts, 'user_id', Auth::id());
        $posts = $this->buildPostsData($posts);
        $post = Mattermost::createPost($posts);

        $channel = $this->getChannelByMattermostId($posts['channel_id']);
        // TODO: need to create mailable for new message
        $user = User::select('id', 'username', 'sex', 'avatar')
            ->where('id', $userId)
            ->first();

        $this->updateUserHasSentMessageIfNeed($channel->id, $userId);

        $params = [
            'title' => __('notification.' . Consts::NOTIFY_TYPE_NEW_MESSAGE),
            'body' => __(Consts::NOTIFY_NEW_MESSAGE, ['username' => $user->username]),
            'data' => ['user' => $user, 'type' => Consts::NOTIFY_NEW_MESSAGE]
        ];

        $this->fcmService->pushNotifcation($channel->getOppositeUserId($userId), $params);

        // Update last_post for channel in cache
        $this->updateChannelWhenNewPost($post, $channel);

        if (!empty($posts['props']['type']) && $posts['props']['type'] === Consts::MESSAGE_PROPS_TYPE_VOICE) {
            $post->message_type = Consts::MESSAGE_TYPE_TEXT_VOICE_MESSAGE;
            event(new NewPostForUser($userId, $post));
            event(new NewPostForUser($channel->getOppositeUserId($userId), $post));
        }

        $this->performFirehosePut($channel, 'Message sent', 'user');

        return $post;
    }

    private function updateUserHasSentMessageIfNeed($channelId, $userId)
    {
        $channelMember = ChannelMember::where('user_id', $userId)
            ->where('channel_id', $channelId)
            ->first();

        if ($channelMember) {
            $channelMember->is_sent_message = Consts::TRUE;
            $channelMember->save();
        }
    }

    private function buildPostsData($posts)
    {
        $userId = array_get($posts, 'user_id', Auth::id());
        $posts['user_id'] = $this->getMattermostUserId($userId);

        $posts['props']['temp_id'] = array_get($posts, 'temp_id');

        if (!empty($posts['images'])) {
            $props = [
                'images' => [$posts['images']]
            ];

            $posts['props'] = array_merge($posts['props'], $props);
        }

        return $posts;
    }

    public function createPostSystem($posts)
    {
        $this->validateChannelBlocked($posts['channel_id']);

        $post = Mattermost::createPostSystem($posts);

        $channel = $this->getChannelByMattermostId($posts['channel_id']);

        $this->updateUserHasSentMessageIfNeed($channel->id, $posts['props']->sender_id);

        // Update last_post for channel in cache
        $this->updateChannelWhenNewPost($post, $channel);

        $this->performFirehosePut($channel, 'Message sent', 'system');

        return $post;
    }

    private function performFirehosePut($channel, $when, $type = null)
    {
        $data = [
            'channel_id' => $channel->channel_id,
            'from_user_id' => Auth::id(),
            'to_user_id' => $channel->getOppositeUserId()
        ];

        if (!empty($type)) {
            $data = array_merge($data, ['type' => $type]);
        }

        Aws::performFirehosePut([
            'when' => $when,
            'data' => $data
        ]);
    }

    private function updateChannelWhenNewPost($post, $channel)
    {
        $userIds = $channel->getUsersOnChannel();

        // user's authentication send a new message.
        if (in_array(Auth::id(), $userIds)) {
            ChatUtils::updateChannelWhenNewPost(Auth::user(), $post);

            $oppositeUser = DB::table('users')->where('id', $channel->getOppositeUserId())->first();
            ChatUtils::updateChannelWhenNewPost($oppositeUser, $post);

            event(new NewPostForOppositeUser($oppositeUser->id, $channel));

            return;
        }

        // system sends a new message.
        foreach ($userIds as $userId) {
            $user = DB::table('users')->where('id', $userId)->first();
            ChatUtils::updateChannelWhenNewPost($user, $post);

            event(new NewPostForOppositeUser($user->id, $channel));
        }
    }

    private function validateChannelBlocked($channelId)
    {
        if ($this->checkChannelBlocked($channelId)) {
            throw new ChannelBlockedException();
        }
    }

    public function updatePost($postId, $params)
    {
        return Mattermost::updatePost($postId, $params);
    }

    public function deletePost($params)
    {
        return Mattermost::deletePost($params);
    }

    public function getPostsForChannel($channelId, $input)
    {
        $pagination = [
            'per_page' => array_get($input, 'limit', 20)
        ];

        if (!empty($input['prev_post_id'])) {
            $pagination['before'] = array_get($input, 'prev_post_id');
        }

        $result = Mattermost::getPostsForChannel($channelId, $pagination);

        $posts = collect($result->posts);

        if ($posts->isEmpty()) {
            return $result;
        }

        $result->prev_post_id = $posts->sortBy('create_at')->first()->id;

        $systemMsgIds = $posts->filter(function ($item) { return !!((array) $item->props); })
            ->pluck('props.system_message_id')
            ->toArray();

        $systemMessages = SessionSystemMessage::whereIn('id', $systemMsgIds)
            ->get()
            ->mapWithKeys(function ($item) { return [$item->id => $item]; })
            ->all();

        $mattermostUserId = MattermostUser::where('user_id', Auth::id())->value('mattermost_user_id');

        $result->posts = $posts->map(function ($item, $key) use ($systemMessages, $mattermostUserId) {
            $item->direction = $this->getPostDirection($item, $systemMessages, $mattermostUserId);
            $item->message_type = Consts::MESSAGE_TYPE_TEXT_MESSAGE;

            $props = (array) $item->props;

            if (!empty($props['type']) && $props['type'] === Consts::MESSAGE_PROPS_TYPE_VOICE) {
                $item->message_type = Consts::MESSAGE_TYPE_TEXT_VOICE_MESSAGE;
                return $item;
            }

            $isNormalMessage = !$props || empty($props['system_message_id']) || empty($systemMessages[$props['system_message_id']]);
            if ($isNormalMessage) {
                return $item;
            }

            $item->message_type = $systemMessages[$item->props->system_message_id]->message_type;
            $item->system_msg = $systemMessages[$item->props->system_message_id];

            return $item;
        });

        return $result;
    }

    private function getPostDirection($item, $systemMessages, $mattermostUserId)
    {
        $props = (array) $item->props;
        $isNormalMessage = !$props || empty($props['system_message_id']) || empty($systemMessages[$props['system_message_id']]);
        if ($isNormalMessage) {
            return $mattermostUserId === $item->user_id ? Consts::MESSAGE_DIRECTION_SENDER : Consts::MESSAGE_DIRECTION_RECEIVER;
        }

        $senderId = $systemMessages[$item->props->system_message_id]->sender_id;
        if (!$senderId) {
            return Consts::MESSAGE_DIRECTION_SYSTEM;
        }

        return Auth::id() === $senderId ? Consts::MESSAGE_DIRECTION_SENDER : Consts::MESSAGE_DIRECTION_RECEIVER;
    }

    public function getChannelsForUser($params = [])
    {
        $params = array_merge($params, [
            'page' => array_get($params, 'page', 1),
            'limit' => array_get($params, 'limit', Consts::DEFAULT_PER_PAGE)
        ]);

        $data = ChatUtils::getChannelsForUser(Auth::user(), $params);
        $data = $this->loadSystemMessageForLastPostIfNeed($data);

        return $data;
    }

    public function getChannelsForUserByIds($mattermostChannelIds)
    {
        $params = [
            'limit' => count($mattermostChannelIds),
            'channel_ids' => $mattermostChannelIds
        ];
        $paginator = ChatUtils::getChannelsForUser(Auth::user(), $params);
        $paginator = $this->loadSystemMessageForLastPostIfNeed($paginator);

        return $paginator->getCollection();
    }

    private function loadSystemMessageForLastPostIfNeed($data)
    {
        $systemMsgIds = $data->getCollection()
            ->pluck('last_post.props.system_message_id')
            ->filter()
            ->toArray();

        $systemMessages = SessionSystemMessage::whereIn('id', $systemMsgIds)
            ->get()
            ->mapWithKeys(function ($item) { return [$item->id => $item]; })
            ->all();

        $data->getCollection()->transform(function ($item) use ($systemMessages) {
            $lastPost = (object) $item->last_post;
            $props = (array) $lastPost->props;
            $isNormalMessage = !$props || empty($props['system_message_id']) || empty($systemMessages[$props['system_message_id']]);

            if ($isNormalMessage) {
                return $item;
            }

            $lastPost->system_msg = $systemMessages[$props['system_message_id']];
            $item->last_post = $lastPost;

            return $item;
        });

        return $data;
    }

    public function viewChannel($channelId)
    {
        $mattermostUserId = $this->getMattermostUserId(Auth::id());
        $viewedChannel = Mattermost::viewChannel($mattermostUserId, $channelId);

        // Update unread messsage.
        ChatUtils::updateChannelWhenViewedChannel(Auth::user(), $channelId);

        return $viewedChannel;
    }

    public function getUnreadMessages($channelId)
    {
        $mattermostUserId = $this->getMattermostUserId(Auth::id());
        return Mattermost::getUnreadMessages($mattermostUserId, $channelId);
    }

    public function searchChannel($keyword)
    {
        $channelUsers = $this->getUsersForSearch($keyword);
        $channelUserGamelancers = $this->getUsersForSearch($keyword, Consts::TRUE);

        return [
            'users' => $channelUsers,
            'gamelancers' => $channelUserGamelancers
        ];
    }

    private function getUsersForSearch($keyword, $isGamelancer = Consts::FALSE)
    {
        $channels = [];
        $keyword = Utils::escapeLike($keyword);
        $userTypeList = $isGamelancer ? [Consts::USER_TYPE_PREMIUM_GAMELANCER, Consts::USER_TYPE_FREE_GAMELANCER] : [Consts::USER_TYPE_USER];
        User::where('username', 'LIKE', "%{$keyword}%")
            ->where('id', '!=', Auth::id())
            ->whereIn('user_type', $userTypeList)
            ->with(['channelMembers.channel'])
            ->get()
            ->map(function ($item, $key) use (&$channels) {
                $channelExists = Channel::getChatChannel($item->id);
                if ($channelExists) {
                    $channel = $this->getChannelById($channelExists->mattermost_channel_id, Auth::id());
                    if ($channel->last_post['message'] && $channel->last_post_at) {
                        $channels[] = $channel;
                    }
                    return $channels;
                }
            })
            ->take(static::LIMITATION_ITEMS);

        return $channels;
    }

    public function getChannelById($channelId, $userId)
    {
        $user = Auth::id() === $userId ? Auth::user() : User::find($userId);
        $channel = ChatUtils::getChannelDetail($user, $channelId);
        $channel = $this->getSystemMessageForChannelIfNeed($channel);

        return $channel;
    }

    public function getChannelByUsername($username)
    {
        $oppositeUser = User::where('username', $username)->first();
        if (!$oppositeUser) {
            throw new UserNotExitstException();
        }

        $user = Auth::user();

        $channelTags = [
            sprintf('%s_%s', $user->id, $oppositeUser->id),
            sprintf('%s_%s', $oppositeUser->id, $user->id)
        ];
        $channel = Channel::whereIn('channel_tag', $channelTags)->first();

        if (!$channel) {
            return null;
        }

        $channel = ChatUtils::getChannelDetail($user, $channel->mattermost_channel_id);
        $channel = $this->getSystemMessageForChannelIfNeed($channel);

        return $channel;
    }

    private function getSystemMessageForChannelIfNeed($channel)
    {
        $lastPost = (object) $channel->last_post;
        $props = (array) $lastPost->props;
        $isNormalMessage = !$props || empty($props['system_message_id']);

        if ($isNormalMessage) {
            return $channel;
        }

        $lastPost->system_msg = SessionSystemMessage::find($props['system_message_id']);
        $channel->last_post = (array) $lastPost;

        return $channel;
    }

    public function handleBlockChannel($channelId, $isBlocked)
    {
        if ($this->checkChannelBlocked($channelId) && $isBlocked) {
            throw new ChannelBlockedException();
        }

        $user = Auth::user();
        $channel = Channel::getChannelByMattermostChannelId($channelId);
        $oppositeUserId = $channel->getOppositeUserId();

        if ($this->checkChannelHasSession($user->id, $oppositeUserId)) {
            throw new ChannelBlockedException('exceptions.channel_has_session');
        }

        $channelMember = $this->getChannelMember($channel, $user->id);
        $channelMember->is_blocked = $isBlocked;
        $channelMember->save();

        $this->sendEventBlockChannelUpdated(['channelId' => $channelId, 'isBlocked' => $isBlocked, 'userId' => $user->id]);

        $oppositeUser = DB::table('users')->where('id', $oppositeUserId)->first();
        $oppositeChannelMember = $this->getChannelMember($channel, $oppositeUserId);

        $isBlocked = $channelMember->is_blocked || $oppositeChannelMember->is_blocked;

        // Save new setting of channel.
        ChatUtils::updateChannelSetting($user, $channelMember, $isBlocked);
        ChatUtils::updateChannelSetting($oppositeUser, $oppositeChannelMember, $isBlocked);

        return [
            'channelId' => $channelId,
            'data' => $channelMember
        ];
    }

    private function checkChannelHasSession($userId, $oppositeUserId)
    {
        $listStatus = [
            Consts::SESSION_STATUS_BOOKED,
            Consts::SESSION_STATUS_STARTING,
            Consts::SESSION_STATUS_RUNNING,
            Consts::SESSION_STATUS_MARK_COMPLETED
        ];
        return Session::whereIn('status', $listStatus)
            ->where(function ($query) use ($userId, $oppositeUserId) {
                $query->where(function ($query2) use ($userId, $oppositeUserId) {
                    $query2->where('claimer_id', $userId)
                        ->where('gamelancer_id', $oppositeUserId);
                })
                ->orWhere(function ($query2) use ($userId, $oppositeUserId) {
                    $query2->where('claimer_id', $oppositeUserId)
                        ->where('gamelancer_id', $userId);
                });
            })
            ->exists();
    }

    private function sendEventBlockChannelUpdated($data)
    {
        $channel = Channel::getChannelByMattermostChannelId($data['channelId']);
        $userIds = $channel->getUsersOnChannel();

        event(new BlockChannelUpdated($userIds[0], $data));
        event(new BlockChannelUpdated($userIds[1], $data));
    }

    public function handleMuteChannel($channelId, $isMuted)
    {
        $channel = Channel::getChannelByMattermostChannelId($channelId);
        $user = Auth::user();

        $channelMember = $this->getChannelMember($channel, $user->id);
        $channelMember->is_muted = $isMuted;
        $channelMember->save();

        ChatUtils::updateChannelSetting($user, $channelMember);
        $data = [
            'channelId' => $channelId,
            'data' => $channelMember
        ];

        event(new MuteChannelUpdated($user->id, $data));

        return $data;
    }

    private function getChannelMember($channel, $userId)
    {
        $channelMember = ChannelMember::where('channel_id', $channel->id)
            ->where('user_id', $userId)
            ->first();

        return $channelMember;
    }

    private function checkChannelBlocked($channelId)
    {
        $channel = Channel::getChannelByMattermostChannelId($channelId);
        return ChannelMember::where('channel_id', $channel->id)
            ->where('is_blocked', Consts::TRUE)
            ->exists();
    }

    private function getChannelByMattermostId($mattermostChannelId)
    {
        return Channel::where('mattermost_channel_id', $mattermostChannelId)
            ->first();
    }

    private function getMattermostUserId($userId)
    {
        return User::find($userId)->mattermostUser->mattermost_user_id;
    }

    public function getMattermostToken()
    {
        return Mattermost::getTokenUser(Auth::user()->email, true);
    }

    public function getTotalChannelsUnreadMessage()
    {
        $user = Auth::user();
        ChatUtils::getChannelsForUser($user);

        $channels = ChatUtils::getChannelsExistsUnreadMessageForUser($user);

        $totalChannelsUnreadMessage = $channels->filter(function ($channel) {
            $currentMember = $channel->current_member;

            return $currentMember->viewed_at < $channel->last_post_at
                    && $currentMember->user_id === Auth::id();
        })
        ->count();

        return $totalChannelsUnreadMessage;
    }

    public function markAsView()
    {
        $user = Auth::user();
        $channelExistsUnreadMessage = ChatUtils::getChannelsExistsUnreadMessageForUser($user);

        $channelExistsUnreadMessage->each(function ($channel, $key) use ($user) {
            $currentMember = $channel->current_member;

            $channelMember = ChannelMember::find($currentMember->id);

            if ($channelMember) {
                $channelMember->viewed_at = Utils::currentMilliseconds();
                $channelMember->save();

                ChatUtils::updateChannelSetting($user, $channelMember);
            }
        });

        return 'ok';
    }
}
