<?php

namespace App\Http\Services;

use App\Consts;
use App\Events\VoiceRoomTypeChanged;
use App\Jobs\CalculateAmaRoomQuestions;
use App\Jobs\CalculateCommunityRoomStatistic;
use App\Models\CommunityMember;
use App\Models\Game;
use App\Utils;
use App\Utils\VoiceGroupUtils;
use Illuminate\Support\Facades\DB;
use Exception;
use App\Models\User;
use App\Models\Channel;
use App\Models\VoiceDiary;
use App\Models\UserFollowing;
use App\Models\ChannelMember;
use App\Models\VoiceChatRoom;
use App\Models\VoiceChatRoomUser;
use App\Models\RoomCategory;
use App\Models\RoomRequest;
use App\Models\RoomInvitation;
use App\Models\RoomReport;
use App\Models\RoomUserReport;
use App\Models\RoomSetting;
use App\Models\RoomQuestion;
use App\Events\IncomingVoiceCall;
use App\Events\DeclineVoiceCall;
use App\Events\PairedVoiceCall;
use App\Events\VoiceRoomClosed;
use App\Events\RoomUserJoined;
use App\Events\ModeratorUpgraded;
use App\Events\ModeratorDowngraded;
use App\Events\SpeakerUpgraded;
use App\Events\SpeakerDowngraded;
use App\Events\RoomUserLeft;
use App\Events\RoomUserKicked;
use App\Events\RoomHostUpgraded;
use App\Events\RoomUserInvited;
use App\Events\RoomInfoUpdated;
use App\Events\RoomUserHandRaised;
use App\Events\UsernameInRoomUpdated;
use App\Events\VoiceChatRoomCreated;
use App\Events\VoiceChatRoomUpdated;
use App\Events\VoiceCategoryUpdated;
use App\Events\UserLeftRoomFromOtherPlatforms;
use App\Events\RoomQuestionAsked;
use App\Events\RoomQuestionRejected;
use App\Events\RoomQuestionAccepted;
use App\Events\RoomQuestionCanceled;
use App\Events\RoomQuestionAnswering;
use App\Events\RoomQuestionAnswered;
use App\Events\RoomSettingChanged;
use Agora;
use Auth;
use Cache;
use App\Exceptions\Reports\InvalidVoiceChannelException;
use App\Exceptions\Reports\ChannelBlockedException;
use App\Exceptions\Reports\InvalidVoiceCallingException;
use App\Exceptions\Reports\UserHaveKickedFromRoomException;
use App\Exceptions\Reports\InvalidActionException;
use App\Exceptions\Reports\RoomMaxSizeException;
use App\Exceptions\Reports\VoiceGroupException;
use App\Jobs\CheckVoiceCallOutdated;
use App\Jobs\CalculateRoomCurrentSize;
use App\Jobs\CalculateRoomCategoryStatistic;
use App\Http\Services\ChatService;
use App\Http\Services\UserService;
use Carbon\Carbon;
use App\Traits\NotificationTrait;

class VoiceService extends BaseService {

    use NotificationTrait;

    private $userService;

    const USER_RANDOM_ROOM_TIME_LIVE = 300; // 5 minutes

    public function __construct()
    {
        $this->userService = new UserService;
    }

    public function createChannel($username)
    {
        $oppsiteUser = User::where('username', $username)->first();

        if (!$oppsiteUser) {
            $this->throwUserInvalid($username);
        }

        $this->validate($oppsiteUser);

        $channel = Channel::getChatChannel($oppsiteUser->id);

        $channelBlock = ChannelMember::where('channel_id', $channel->id)
            ->where('is_blocked', Consts::TRUE)
            ->exists();

        if ($channelBlock) {
            throw new ChannelBlockedException();
        }

        $followEachOther = UserFollowing::userHasFollowerByFollowingId(Auth::id(), $oppsiteUser->id)
            && UserFollowing::userHasFollowerByFollowingId($oppsiteUser->id, Auth::id());

        $chatEachOther = ChannelMember::userHasSendedMessgae($channel->id, Auth::id())
            && ChannelMember::userHasSendedMessgae($channel->id, $oppsiteUser->id);

        if (!$followEachOther && !$chatEachOther) {
            $this->throwFollowOrChatInvalid();
        }

        $currentMilis = time();
        $hash = gamelancer_hash("$channel->channel_tag_{$currentMilis}");

        VoiceDiary::create([
            'channel_id'    => $channel->id,
            'hash'          => $hash,
            'caller_id'     => Auth::id(),
            'receiver_id'   => $channel->getOppositeUserId(),
            'status'        => Consts::VOICE_STATUS_CREATED
        ]);

        return $hash;
    }

    private function validate($oppsiteUser)
    {
        if ($this->getVoiceDiaryInCalling(Auth::id())) {
            $message = __('exceptions.voice_channel.calling.current_user_invalid');
            $this->throwVoiceCallingInvalid($message);
        }

        if ($this->getVoiceDiaryInCalling($oppsiteUser->id)) {
            $message = __('exceptions.voice_channel.calling.invalid', ['username' => $oppsiteUser->username]);
            $this->throwVoiceCallingInvalid($oppsiteUser->username);
        }
    }

    private function getVoiceDiaryInCalling($userId)
    {
        return VoiceDiary::where(function ($query) use ($userId) {
                $query->where('caller_id', $userId)
                    ->orWhere('receiver_id', $userId);
            })
            ->whereNotIn('status', [
                Consts::VOICE_STATUS_CREATED,
                Consts::VOICE_STATUS_DECLINE,
                Consts::VOICE_STATUS_ENDED_CALL
            ])
            ->first();
    }

    public function joinChannel($hashVoiceChannel)
    {
        $voiceDiary = $this->getVoiceDiaryByHashVoiceChannel($hashVoiceChannel);

        if (!in_array($voiceDiary->status, [Consts::VOICE_STATUS_CREATED, Consts::VOICE_STATUS_CALLING])) {
            $this->throwVoiceChannelInvalid();
        }

        $voiceDiary->status = Consts::VOICE_STATUS_CALLING;
        $voiceDiary->save();

        list($caller, $receiver) = $this->fireEventIncomingVoiceCall($voiceDiary);

        CheckVoiceCallOutdated::addVoice($voiceDiary);

        $agoraUid = Auth::id();
        $token = Agora::generateRtcToken($voiceDiary->hash, $agoraUid);

        return [
            'token'     => $token,
            'user'      => Auth::id() === $caller->id ? $receiver : $caller,
            'caller'    => $caller
        ];
    }

    public function fireEventIncomingVoiceCallIfNeed($userId)
    {
        $voiceDiary = VoiceDiary::where('receiver_id', $userId)
            ->where('status', Consts::VOICE_STATUS_CALLING)
            ->first();

        if (!$voiceDiary) {
            return;
        }

        $this->fireEventIncomingVoiceCall($voiceDiary);
    }

    private function fireEventIncomingVoiceCall($voiceDiary)
    {
        $caller = $this->getUserInfo($voiceDiary->caller_id);
        $receiver = $this->getUserInfo($voiceDiary->receiver_id);

        $data = [
            'channel_id'    => $voiceDiary->hash,
            'caller'        => $caller,
            'receiver'      => $receiver
        ];

        event(new IncomingVoiceCall($receiver->id, $data));

        return [$caller, $receiver];
    }

    public function declineCall($hashVoiceChannel)
    {
        $voiceDiary = $this->getVoiceDiaryByHashVoiceChannel($hashVoiceChannel);

        $voiceDiary->status = Consts::VOICE_STATUS_DECLINE;
        $voiceDiary->save();

        $data = [
            'channel_id'    => $voiceDiary->hash
        ];

        event(new DeclineVoiceCall($voiceDiary->caller_id, $data));
        event(new DeclineVoiceCall($voiceDiary->receiver_id, $data));

        CheckVoiceCallOutdated::removeVoice($voiceDiary);

        $this->createPostForDeclineCall($voiceDiary);

        return $voiceDiary;
    }

    public function pairCall($hashVoiceChannel)
    {
        $voiceDiary = $this->getVoiceDiaryByHashVoiceChannel($hashVoiceChannel);

        $voiceDiary->started_time = now();
        $voiceDiary->status = Consts::VOICE_STATUS_PAIRED;
        $voiceDiary->save();

        $data = [
            'channel_id'    => $voiceDiary->hash,
            'started_time'  => $voiceDiary->started_time
        ];

        event(new PairedVoiceCall($voiceDiary->caller_id, $data));
        event(new PairedVoiceCall($voiceDiary->receiver_id, $data));

        CheckVoiceCallOutdated::removeVoice($voiceDiary);

        return $voiceDiary;
    }

    public function endCall($hashVoiceChannel)
    {
        $voiceDiary = $this->getVoiceDiaryByHashVoiceChannel($hashVoiceChannel);

        $voiceDiary->ended_time = now();
        $voiceDiary->status = Consts::VOICE_STATUS_ENDED_CALL;
        $voiceDiary->save();

        $this->createPostForEndCall($voiceDiary);

        return $voiceDiary;
    }

    private function createPostForEndCall($voiceDiary)
    {
        $caller = $this->getUserInfo($voiceDiary->caller_id);
        if (Auth::check() && $caller->id !== Auth::id()) {
            return;
        }

        $data = $this->buildPostDefaultData($voiceDiary, $caller);
        $data['message'] = Consts::VOICE_AUDIO_CALL;
        $data['props']['time'] = $this->calculateTimeCalling($voiceDiary);
        $data['props']['status'] = Consts::VOICE_STATUS_ENDED_CALL;

        $this->createPost($data);
    }

    private function createPostForDeclineCall($voiceDiary)
    {
        $caller = $this->getUserInfo($voiceDiary->caller_id);

        $data = $this->buildPostDefaultData($voiceDiary, $caller);
        $data['message'] = Consts::VOICE_MISSED_CALL;
        $data['props']['missed_call_time'] = Carbon::parse($voiceDiary->updated_at)->timestamp * 1000;
        $data['props']['status'] = Consts::VOICE_STATUS_DECLINE;

        $this->createPost($data);
    }

    private function createPost($data)
    {
        $chatService = new ChatService;
        $chatService->createPost($data);
    }

    private function buildPostDefaultData($voiceDiary, $caller)
    {
        $channel = Channel::find($voiceDiary->channel_id);

        $data = [
            'channel_id' => $channel->mattermost_channel_id,
            'props' => [
                'type' => Consts::MESSAGE_PROPS_TYPE_VOICE,
                'caller_id' => $caller->id,
            ],
            'temp_id' => uniqid(),
            'login_id' => $caller->email,
            'user_id' => $caller->id
        ];

        return $data;
    }

    private function calculateTimeCalling($voiceDiary)
    {
        return Carbon::parse($voiceDiary->ended_time)->diffInSeconds($voiceDiary->started_time);
    }

    private function getVoiceDiaryByHashVoiceChannel($hashVoiceChannel)
    {
        $voiceDiary = VoiceDiary::where('hash', $hashVoiceChannel)->first();
        if (!$voiceDiary) {
            $this->throwVoiceChannelInvalid();
        }

        return $voiceDiary;
    }

    private function getUserInfo($userId)
    {
        $user = DB::table('users')
            ->join('user_settings', 'user_settings.id', 'users.id')
            ->leftJoin('user_rankings', 'user_rankings.user_id', 'users.id')
            ->select('users.*', 'user_settings.online', 'user_rankings.ranking_id')
            ->where('users.id', $userId)
            ->first();

        if (!$user) {
            $this->throwUserInvalid($username);
        }

        return (object) [
            'id'                => $user->id,
            'username'          => $user->username,
            'email'             => $user->email,
            'sex'               => $user->sex,
            'avatar'            => $user->avatar,
            'user_type'         => $user->user_type,
            'ranking_id'        => $user->ranking_id,
            'setting'           => [
                'online' => $user->online
            ]
        ];
    }

    private function throwUserInvalid ($username)
    {
        throw new Exception("Something wrong! The user <{$username}> is invalid.");
    }

    private function throwVoiceChannelInvalid ()
    {
        throw new InvalidVoiceChannelException();
    }

    private function throwFollowOrChatInvalid()
    {
        throw new InvalidVoiceChannelException('exceptions.voice_channel.follow_or_chat.invalid');
    }

    private function throwVoiceCallingInvalid($message)
    {
        throw new InvalidVoiceCallingException($message);
    }


    // Voice Group
    public function createVoiceChatRoom($params)
    {
        $this->checkUserInOtherRoom();

        $user = User::withoutAppends()
            ->leftJoin('voice_group_managers', 'voice_group_managers.user_id', 'users.id')
            ->select('users.id', 'users.username', 'users.sex', 'users.avatar',
                DB::raw('(CASE WHEN voice_group_managers.deleted_at IS NULL THEN voice_group_managers.role ELSE NULL END) AS voice_group_role'))
            ->where('users.id', Auth::id())
            ->first();

        $currentMilis = time();
        $username = $user->username;
        $hash = gamelancer_hash("{$username}_{$currentMilis}");

        $params = array_filter($params);

        $room = VoiceChatRoom::create([
            'user_id'           => $user->id,
            'creator_id'        => $user->id,
            'pinned'            => $user->voice_group_role === Consts::VOICE_GROUP_ROLE_ADMIN ? Consts::TRUE : Consts::FALSE,
            'game_id'           => array_get($params, 'game_id'),
            'is_private'        => array_get($params, 'is_private', 0),
            'name'              => $hash,
            'title'             => array_get($params, 'title'),
            'topic'             => array_get($params, 'topic'),
            'type'              => array_get($params, 'type', Consts::ROOM_TYPE_HANGOUT),
            'size'              => array_get($params, 'size', 100),
            'current_size'      => 1,
            'code'              => array_get($params, 'code'),
            'rules'             => array_get($params, 'rules'),
            'background_url'    => array_get($params, 'background_url'),
            'status'            => Consts::VOICE_ROOM_STATUS_CALLING
        ]);

        $voiceChatRoomUser = $this->createVoiceChatRoomUser(
            $room,
            [
                'user_id'           => $user->id
            ],
            [
                'type'              => Consts::ROOM_USER_TYPE_HOST,
                'sid'               => array_get($params, 'sid'),
                'username'          => array_get($params, 'username'),
                'started_time'      => now()
            ]
        );

        if ($room->type === Consts::ROOM_TYPE_AMA) {
            $roomSettings = RoomSetting::create([
                'room_id' => $room->id,
                'allow_ask_question' => Consts::TRUE
            ]);

            $room->allow_ask_question = $roomSettings->allow_ask_question;
        }

        $rtcToken = Agora::generateRtcToken($hash, $user->id);
        $rtmToken = Agora::generateRtmToken(strval($user->id));

        $friendToInvite = array_get($params, 'friend_id');
        if ($friendToInvite) {
            $this->inviteUserIntoRoom(['room_id' => $room->id, 'user_id' => $friendToInvite]);
        }

        $room->user = $user;
        event(new VoiceChatRoomCreated($room));

        CalculateRoomCategoryStatistic::dispatch($room->game_id)->onQueue(Consts::QUEUE_VOICE_GROUP);

        $this->fireEventVoiceCategoryUpdated($room->game_id);

        if (!$room->is_private) {
            $this->sendNotificationToFriends($room, $friendToInvite);
        }

        return $room;
    }

    private function sendNotificationToFriends($room, $exceptId = null)
    {
        $friends = $this->userService->getListFriend();
        $friends->each(function ($user, $key) use ($room, $exceptId) {
            if ($user->id !== $exceptId) {
                $notificationParams = [
                    'user_id' => $user->id,
                    'type' => Consts::NOTIFY_TYPE_VOICE_ROOM_CREATED,
                    'message' => Consts::MESSAGE_NOTIFY_ROOM_CREATED,
                    'props' => [
                        'room_category' => $room->game_id,
                        'room_title' => $room->title
                    ],
                    'data' => [
                        'user'      => (object) ['id' => $room->user_id],
                        'room_name' => $room->name
                    ]
                ];
                $this->fireNotification(Consts::NOTIFY_TYPE_VOICE_ROOM, $notificationParams);
            }
        });
    }

    public function updateVoiceChatRoom($data)
    {
        $room = VoiceChatRoom::where('id', $data['room_id'])
            ->where('status', Consts::VOICE_ROOM_STATUS_CALLING)
            ->first();
        if (!$room) {
            throw new InvalidActionException();
        }

        $host = VoiceChatRoomUser::where('room_id', $data['room_id'])
            ->where('type', Consts::ROOM_USER_TYPE_HOST)
            ->where('user_id', Auth::id())
            ->whereNull('ended_time')
            ->first();
        if (!$host) {
            throw new InvalidActionException();
        }

        if (array_key_exists('is_private', $data)) {
            $room->is_private = $data['is_private'];
        }

        if (array_key_exists('title', $data)) {
            $room->title = array_get($data, 'title') ? $data['title'] : VoiceGroupUtils::buildTitle($room->game_id, $room->type);
        }

        if (array_key_exists('topic', $data)) {
            $room->topic = $data['topic'];
        }

        if (array_key_exists('code', $data)) {
            $room->code = $data['code'];
        }

        if (!empty(array_get($data, 'size'))) {
            $totalUser = VoiceChatRoomUser::where('room_id', $data['room_id'])
                ->whereNull('ended_time')
                ->count();
            if ($totalUser > intval($data['size'])) {
                throw new VoiceGroupException('exceptions.users_oversize');
            }
            $room->size = intval($data['size']);
        }

        $room->save();

        $username = array_get($data, 'username');
        if (!empty($username) && $username !== $host->username) {
            $host->username = $username;
            $host->save();

            $host->user = $this->getUser($host->user_id);
            event(new UsernameInRoomUpdated($host));
        }

        event(new RoomInfoUpdated($room));

        if (array_key_exists('is_private', $data)) {
            if ($room->type == Consts::ROOM_TYPE_COMMUNITY) {
                CalculateCommunityRoomStatistic::dispatch($room->community_id)->onQueue(Consts::QUEUE_COMMUNITY);
            } else {
                CalculateRoomCategoryStatistic::dispatch($room->game_id)->onQueue(Consts::QUEUE_VOICE_GROUP);
            }
            $userInfo = $this->getUser($host->user_id);
            $room->user = $userInfo;
            $host->user = $userInfo;
            $room->host = $host;
            $room->video = VoiceGroupUtils::getShareVideoByRoomId($room->id);
            event(new VoiceRoomTypeChanged($room));
        }

        return $room;
    }

    private function createVoiceChatRoomUser($voiceChatRoom, $condition, $data)
    {
        return $voiceChatRoom->voiceChatRoomUser()->updateOrCreate($condition, $data);
    }

    public function checkRoomAvailable($name)
    {
        $room = VoiceChatRoom::where('name', $name)->first();
        if ($room->status === Consts::VOICE_ROOM_STATUS_ENDED) {
            throw new VoiceGroupException('exceptions.room_ended');
        }

        $userId = Auth::id();
        $count = VoiceChatRoomUser::where('room_id', $room->id)
            ->where('user_id', '!=', $userId)
            ->whereNull('ended_time')
            ->count();

        if ($count >= $room->size) {
            throw new RoomMaxSizeException();
        }

        $roomUser = VoiceChatRoomUser::where('room_id', $room->id)
            ->where('user_id', $userId)
            ->where('is_kicked', Consts::TRUE)
            ->first();

        if ($roomUser) {
            throw new UserHaveKickedFromRoomException();
        }

        return $room;
    }

    public function checkUserCanJoinRoom($name, $params = [])
    {
        $room = VoiceChatRoom::where('name', $name)->first();
        $this->checkUserInOtherRoom($room->id);
        $this->checkUserUsingOtherPlatform($room->id, $params);
        return $room;
    }

    public function checkUserInOtherRoom($roomId = null)
    {
        $userId = Auth::id();
        $roomUser = VoiceChatRoomUser::join('voice_chat_rooms', 'voice_chat_rooms.id', 'voice_chat_room_users.room_id')
            ->where('voice_chat_room_users.user_id', $userId)
            ->whereNull('voice_chat_room_users.ended_time')
            ->whereIn('voice_chat_rooms.status', [
                Consts::VOICE_ROOM_STATUS_CREATED,
                Consts::VOICE_ROOM_STATUS_CALLING
            ])
            ->when($roomId, function ($query) use ($roomId) {
                $query->where('voice_chat_rooms.id', '!=', $roomId);
            })
            ->exists();

        if ($roomUser) {
            throw new VoiceGroupException('exceptions.user_in_another_room');
        }
    }

    private function checkUserUsingOtherPlatform($roomId, $params = [])
    {
        $userInOtherPlatform = VoiceChatRoomUser::where('room_id', $roomId)
            ->where('user_id', Auth::id())
            ->whereNull('ended_time')
            ->when(!empty(array_get($params, 'sid')), function ($query) use ($params) {
                $query->where('sid', '!=', array_get($params, 'sid'));
            }, function ($query) {
                $query->whereNotNull('sid');
            })
            ->exists();

        if ($userInOtherPlatform) {
            throw new VoiceGroupException('exceptions.user_use_another_platform');
        }
    }

    public function joinVoiceChatRoom($name, $params = [])
    {
        $this->checkRoomAvailable($name);
        $room = $this->checkUserCanJoinRoom($name, $params);

        $userId = Auth::id();

        $roomUser = VoiceChatRoomUser::where('room_id', $room->id)
            ->where('user_id', $userId)
            ->whereNull('ended_time')
            ->when(array_get($params, 'sid'), function ($query) use ($params) {
                $query->where('sid', array_get($params, 'sid'));
            })
            ->first();

        $currentMilis = time();
        $hashSid = gamelancer_hash("{$room->id}-{$userId}-{$currentMilis}");
        $sid = array_get($params, 'sid') ?: $hashSid;

        if (empty($roomUser)) {
            $roomUser = $this->createNewRoomUser($room, $sid, $params);
        } else {
            $roomUser->sid = $sid;
            $roomUser->save();
        }

        $rtcToken = Agora::generateRtcToken($room->name, $userId);
        $rtmToken = Agora::generateRtmToken(strval($userId));

        $this->fireEventVoiceChatRoomUpdated($room->id);
        $this->fireEventVoiceCategoryUpdated($room->game_id);

        return [
            'rtc_token'             => $rtcToken,
            'rtm_token'             => $rtmToken,
            'room'                  => $this->getRoomTotalDetail($name),
            'room_user'             => $roomUser,
            'sid'                   => $roomUser->sid
        ];
    }

    private function createNewRoomUser($room, $sid, $params)
    {
        $type = $room->type === Consts::ROOM_TYPE_PLAY
            ? Consts::ROOM_USER_TYPE_SPEAKER
            : Consts::ROOM_USER_TYPE_GUEST;

        $userId = Auth::id();

        $invitation = RoomInvitation::where('id', array_get($params, 'invitation_id'))
            ->where('receiver_id', $userId)
            ->first();
        if ($invitation) {
            $type = $invitation->type;
        }

        $roomUser = $this->createVoiceChatRoomUser(
            $room,
            [
                'user_id'           => $userId
            ],
            [
                'invited_user_id'   => $invitation ? $invitation->sender_id : null,
                'type'              => $type,
                'sid'               => $sid,
                'username'          => array_get($params, 'username'),
                'started_time'      => now()
            ]
        );
        $roomUser->user = $this->getUser($roomUser->user_id);
        $roomUser->invited_user = $invitation ? $this->getUser($invitation->sender_id) : null;

        event(new RoomUserJoined($roomUser));

        CalculateRoomCurrentSize::dispatch($room->id)->onQueue(Consts::QUEUE_VOICE_GROUP);
        CalculateRoomCategoryStatistic::dispatch($room->game_id)->onQueue(Consts::QUEUE_VOICE_GROUP);
        return $roomUser;
    }

    public function makeHost($userId, $roomId)
    {
        $newHost = VoiceChatRoomUser::where('room_id', $roomId)
            ->where('user_id', $userId)
            ->where('type', '!=', Consts::ROOM_USER_TYPE_HOST)
            ->whereNull('ended_time')
            ->first();
        if (!$newHost) {
            throw new InvalidActionException();
        }

        $oldHost = VoiceChatRoomUser::where('room_id', $roomId)
            ->where('user_id', Auth::id())
            ->whereNull('ended_time')
            ->where('type', Consts::ROOM_USER_TYPE_HOST)
            ->first();
        if (!$oldHost) {
            throw new VoiceGroupException('exceptions.not_permission_make_host');
        }

        $newHost->type = Consts::ROOM_USER_TYPE_HOST;
        $newHost->save();

        $this->updateRaiseHand($roomId, $userId);

        $newHost->user = $this->getUser($newHost->user_id);

        $room = VoiceChatRoom::where('id', $roomId)->first();
        $room->user_id = $newHost->user_id;
        $room->save();

        $isPlayRoom = $room->type === Consts::ROOM_TYPE_PLAY;
        $oldHost->type = $isPlayRoom ? Consts::ROOM_USER_TYPE_SPEAKER : Consts::ROOM_USER_TYPE_MODERATOR;
        $oldHost->save();

        $oldHost->user = $this->getUser($oldHost->user_id);

        event(new RoomHostUpgraded($newHost));
        if ($isPlayRoom) {
            event(new SpeakerUpgraded($oldHost));
        } else {
            event(new ModeratorUpgraded($oldHost));
        }

        $this->fireEventVoiceChatRoomUpdated($room->id);

        return $newHost;
    }

    public function makeModerator($userId, $roomId)
    {
        $roomUser = VoiceChatRoomUser::where('room_id', $roomId)
            ->where('user_id', $userId)
            ->where('type', '!=', Consts::ROOM_USER_TYPE_HOST)
            ->whereNull('ended_time')
            ->first();
        if (!$roomUser) {
            throw new InvalidActionException();
        }

        $host = VoiceChatRoomUser::where('room_id', $roomId)
            ->where('user_id', Auth::id())
            ->whereNull('ended_time')
            ->where('type', Consts::ROOM_USER_TYPE_HOST)
            ->exists();
        if (!$host) {
            throw new VoiceGroupException('exceptions.not_permission_make_moderator');
        }

        $roomUser->type = Consts::ROOM_USER_TYPE_MODERATOR;
        $roomUser->save();

        $this->updateRaiseHand($roomId, $userId);

        $roomUser->user = $this->getUser($roomUser->user_id);
        event(new ModeratorUpgraded($roomUser));

        return $roomUser;
    }

    public function removeModerator($userId, $roomId)
    {
        $roomUser = VoiceChatRoomUser::where('room_id', $roomId)
            ->where('user_id', $userId)
            ->whereNull('ended_time')
            ->where('type', Consts::ROOM_USER_TYPE_MODERATOR)
            ->first();
        if (!$roomUser) {
            throw new InvalidActionException();
        }

        $mod = VoiceChatRoomUser::where('room_id', $roomId)
            ->where('user_id', Auth::id())
            ->whereNull('ended_time')
            ->where('type', Consts::ROOM_USER_TYPE_HOST)
            ->exists();
        if (!$mod) {
            throw new VoiceGroupException('exceptions.not_permission_remove_moderator');
        }

        $roomUser->type = Consts::ROOM_USER_TYPE_SPEAKER;
        $roomUser->save();

        $roomUser->user = $this->getUser($roomUser->user_id);
        event(new ModeratorDowngraded($roomUser));

        return $roomUser;
    }

    public function makeSpeaker($userId, $roomId)
    {
        $voiceChatRoomUser = VoiceChatRoomUser::where('room_id', $roomId)
            ->where('user_id', $userId)
            ->whereNull('ended_time')
            ->where('type', Consts::ROOM_USER_TYPE_GUEST)
            ->first();
        if (!$voiceChatRoomUser) {
            throw new InvalidActionException();
        }

        $mod = VoiceChatRoomUser::where('room_id', $roomId)
            ->where('user_id', Auth::id())
            ->whereNull('ended_time')
            ->whereIn('type', [Consts::ROOM_USER_TYPE_HOST, Consts::ROOM_USER_TYPE_MODERATOR])
            ->exists();
        if (!$mod) {
            throw new VoiceGroupException('exceptions.not_permission_make_speaker');
        }

        $voiceChatRoomUser->type = Consts::ROOM_USER_TYPE_SPEAKER;
        $voiceChatRoomUser->save();

        $this->updateRaiseHand($roomId, $userId);

        $voiceChatRoomUser->user = $this->getUser($voiceChatRoomUser->user_id);
        event(new SpeakerUpgraded($voiceChatRoomUser));

        return $voiceChatRoomUser;
    }

    public function removeSpeaker($userId, $roomId)
    {
        $voiceChatRoomUser = VoiceChatRoomUser::where('room_id', $roomId)
            ->where('user_id', $userId)
            ->whereNull('ended_time')
            ->where('type', Consts::ROOM_USER_TYPE_SPEAKER)
            ->first();
        if (!$voiceChatRoomUser) {
            throw new InvalidActionException();
        }

        $mod = VoiceChatRoomUser::where('room_id', $roomId)
            ->where('user_id', Auth::id())
            ->whereNull('ended_time')
            ->whereIn('type', [Consts::ROOM_USER_TYPE_HOST, Consts::ROOM_USER_TYPE_MODERATOR])
            ->exists();
        if (!$mod) {
            throw new VoiceGroupException('exceptions.not_permission_remove_speaker');
        }

        $voiceChatRoomUser->type = Consts::ROOM_USER_TYPE_GUEST;
        $voiceChatRoomUser->save();

        $voiceChatRoomUser->user = $this->getUser($voiceChatRoomUser->user_id);
        event(new SpeakerDowngraded($voiceChatRoomUser));

        return $voiceChatRoomUser;
    }

    public function closeRoom($roomId)
    {
        $user = Auth::user();

        $voiceChatRoomUser = VoiceChatRoomUser::where('room_id', $roomId)
            ->where('user_id', $user->id)
            ->whereNull('ended_time')
            ->where('type', Consts::ROOM_USER_TYPE_HOST)
            ->first();

        $voiceChatRoom = VoiceChatRoom::where('id', $roomId)
            ->where('status', Consts::VOICE_ROOM_STATUS_CALLING)
            ->first();

        if (!$voiceChatRoomUser || !$voiceChatRoom) {
            throw new InvalidActionException();
        }

        $this->clearShareVideo($roomId, $user->id);

        $voiceChatRoom->voiceChatRoomUser()->update([
            'ended_time' => now()
        ]);

        $voiceChatRoom->status = Consts::VOICE_ROOM_STATUS_ENDED;
        $voiceChatRoom->save();

        event(new VoiceRoomClosed($voiceChatRoom->id));

        CalculateRoomCurrentSize::dispatch($roomId)->onQueue(Consts::QUEUE_VOICE_GROUP);
        CalculateRoomCategoryStatistic::dispatch($voiceChatRoom->game_id)->onQueue(Consts::QUEUE_VOICE_GROUP);

        $this->fireEventVoiceCategoryUpdated($voiceChatRoom->game_id);

        if ($voiceChatRoom->type === Consts::ROOM_TYPE_COMMUNITY) {
            CalculateCommunityRoomStatistic::dispatch($voiceChatRoom->community_id)->onQueue(Consts::QUEUE_COMMUNITY);
        }

        return $voiceChatRoom;
    }

    public function leaveVoiceChatRoom($roomId, $userId = null, $isNextRoom = false)
    {
        $userId = $userId ?: Auth::id();

        $voiceChatRoomUser = VoiceChatRoomUser::where('room_id', $roomId)
            ->where('user_id', $userId)
            ->whereNull('ended_time')
            ->first();

        $voiceChatRoom = VoiceChatRoom::where('id', $roomId)
            ->where('status', Consts::VOICE_ROOM_STATUS_CALLING)
            ->first();

        if (!$voiceChatRoomUser || !$voiceChatRoom) {
            throw new InvalidActionException();
        }

        $voiceChatRoomUser->ended_time = now();
        $voiceChatRoomUser->save();

        $this->updateRaiseHand($roomId, $userId, Consts::ROOM_REQUEST_STATUS_CANCELED);

        $voiceChatRoom = $this->closeRoomIfNeed($voiceChatRoom, $userId);
        if ($voiceChatRoom->status === Consts::VOICE_ROOM_STATUS_ENDED) {
            $this->fireEventVoiceCategoryUpdated($voiceChatRoom->game_id);
            VoiceGroupUtils::clearShareVideo($roomId);
            CalculateRoomCurrentSize::dispatch($roomId)->onQueue(Consts::QUEUE_VOICE_GROUP);
            CalculateRoomCategoryStatistic::dispatch($voiceChatRoom->game_id)->onQueue(Consts::QUEUE_VOICE_GROUP);

            if ($voiceChatRoom->type === Consts::ROOM_TYPE_COMMUNITY) {
                CalculateCommunityRoomStatistic::dispatch($voiceChatRoom->community_id)->onQueue(Consts::QUEUE_COMMUNITY);
            }

            return $voiceChatRoom;
        }
        $this->transferOwnerIfNeed($voiceChatRoom);

        $voiceChatRoomUser->user = $this->getUser($voiceChatRoomUser->user_id);
        $voiceChatRoomUser->is_next_room = $isNextRoom ? 1 : 0;
        event(new RoomUserLeft($voiceChatRoomUser));

        $this->fireEventVoiceChatRoomUpdated($voiceChatRoom->id);
        $this->fireEventVoiceCategoryUpdated($voiceChatRoom->game_id);

        CalculateRoomCurrentSize::dispatch($roomId)->onQueue(Consts::QUEUE_VOICE_GROUP);
        CalculateRoomCategoryStatistic::dispatch($voiceChatRoom->game_id)->onQueue(Consts::QUEUE_VOICE_GROUP);

        if ($voiceChatRoom->type === Consts::ROOM_TYPE_COMMUNITY) {
            CalculateCommunityRoomStatistic::dispatch($voiceChatRoom->community_id)->onQueue(Consts::QUEUE_COMMUNITY);
        }

        return $this->getRoomTotalDetail($voiceChatRoom->name);
    }

    public function leaveAnyRoom()
    {
        $currentRoom = $this->getCurrentRoom();
        if ($currentRoom) {
            return $this->leaveVoiceChatRoom($currentRoom->room_id);
        }
        return $currentRoom;
    }

    public function leaveRoomForUserWithSid($userId, $sid, $roomId)
    {
        $voiceChatRoomUser = VoiceChatRoomUser::where('room_id', $roomId)
            ->where('user_id', $userId)
            ->where('sid', $sid)
            ->whereNull('ended_time')
            ->first();

        if (!$voiceChatRoomUser) {
            throw new InvalidActionException();
        }

        return $this->leaveVoiceChatRoom($voiceChatRoomUser->room_id, $userId);
    }

    public function forceJoinRoom($name, $params)
    {
        $userId = Auth::id();
        $room = $this->checkRoomAvailable($name);
        $currentRoomUser = $this->getCurrentRoom();

        if (!$currentRoomUser) {
            return $this->joinVoiceChatRoom($name, $params);
        }

        if ($room->id !== $currentRoomUser->room_id) {
            $this->leaveVoiceChatRoom($currentRoomUser->room_id);
            return $this->joinVoiceChatRoom($name, $params);
        }

        $currentMilis = time();
        $hashSid = gamelancer_hash("{$room->id}-{$userId}-{$currentMilis}");
        $sid = array_get($params, 'sid') ?: $hashSid;

        $roomUser = VoiceChatRoomUser::where('room_id', $currentRoomUser->room_id)
            ->where('user_id', $userId)
            ->whereNull('ended_time')
            ->first();
        $roomUser->sid = $sid;
        $roomUser->save();

        $rtcToken = Agora::generateRtcToken($room->name, $userId);
        $rtmToken = Agora::generateRtmToken(strval($userId));

        event(new UserLeftRoomFromOtherPlatforms($roomUser));

        return [
            'rtc_token'             => $rtcToken,
            'rtm_token'             => $rtmToken,
            'room'                  => $this->getRoomTotalDetail($name),
            'room_user'             => $roomUser,
            'sid'                   => $roomUser->sid
        ];
    }

    public function forceCreateRoom($params)
    {
        $curentRoom = $this->getCurrentRoom();
        if ($curentRoom->room_id) {
            $this->leaveVoiceChatRoom($curentRoom->room_id);
        }

        return $this->createVoiceChatRoom($params);
    }

    private function transferOwnerIfNeed($room)
    {
        $host = VoiceChatRoomUser::where('room_id', $room->id)
            ->whereNull('ended_time')
            ->where('type', Consts::ROOM_USER_TYPE_HOST)
            ->exists();
        if ($host) {
            return;
        }

        $voiceChatRoomUser = VoiceChatRoomUser::where('room_id', $room->id)
            ->whereNull('ended_time')
            ->when($room->type !== Consts::ROOM_TYPE_PLAY,
                function ($query) {
                    $queryOrder = "CASE WHEN type = '" . Consts::ROOM_USER_TYPE_MODERATOR ."' THEN 1 ";
                    $queryOrder .= "WHEN type = '" . Consts::ROOM_USER_TYPE_SPEAKER ."' THEN 2 ";
                    $queryOrder .= "ELSE 3 END";

                    $query->whereIn('type', [Consts::ROOM_USER_TYPE_MODERATOR, Consts::ROOM_USER_TYPE_SPEAKER])
                        ->orderByRaw($queryOrder);
                },
                function ($query) {
                    $query->where('type', Consts::ROOM_USER_TYPE_SPEAKER);
                }
            )
            ->first();

        if (!$voiceChatRoomUser) {
            return;
        }

        $voiceChatRoomUser->type = Consts::ROOM_USER_TYPE_HOST;
        $voiceChatRoomUser->save();

        $room->user_id = $voiceChatRoomUser->user_id;
        $room->save();

        $voiceChatRoomUser->user = $this->getUser($voiceChatRoomUser->user_id);
        event(new RoomHostUpgraded($voiceChatRoomUser));

        return $voiceChatRoomUser;
    }

    private function closeRoomIfNeed($room, $userId)
    {
        $roomUser = VoiceChatRoomUser::where('room_id', $room->id)
            ->whereNull('ended_time')
            ->where('user_id', '!=', $userId)
            ->whereIn('type', [Consts::ROOM_USER_TYPE_HOST, Consts::ROOM_USER_TYPE_MODERATOR, Consts::ROOM_USER_TYPE_SPEAKER])
            ->exists();
        if ($roomUser) {
            return $room;
        }

        $room->status = Consts::VOICE_ROOM_STATUS_ENDED;
        $room->save();

        $room->roomRequest()->update([
            'status' => Consts::ROOM_REQUEST_STATUS_CANCELED
        ]);

        $room->voiceChatRoomUser()->update([
            'ended_time' => now()
        ]);

        event(new VoiceRoomClosed($room->id));

        return $room;
    }

    public function inviteUserIntoRoom($params, $isInstantHangout = false)
    {
        $roomId = array_get($params, 'room_id');
        $receiverId = array_get($params, 'user_id');
        $sender = Auth::user();

        $room = VoiceChatRoom::where('id', $roomId)
            ->where('status', Consts::VOICE_ROOM_STATUS_CALLING)
            ->first();
        if (!$room) {
            throw new InvalidActionException();
        }

        VoiceChatRoomUser::where('room_id', $roomId)
            ->where('user_id', $receiverId)
            ->where('is_kicked', Consts::TRUE)
            ->update(['is_kicked' => Consts::FALSE]);

        $alreadyInvited = RoomInvitation::where('room_id', $roomId)
            ->where('receiver_id', $receiverId)
            ->where('sender_id', $sender->id)
            ->exists();

        if ($alreadyInvited) {
            throw new VoiceGroupException('exceptions.already_invited_to_room');
        }
        $type = in_array($room->type, [Consts::ROOM_TYPE_PLAY]) || $isInstantHangout ? Consts::ROOM_INVITATION_TYPE_SPEAKER : Consts::ROOM_INVITATION_TYPE_GUEST;

        if ($room->type === Consts::ROOM_TYPE_COMMUNITY) {
            $type = $this->getUserTypeVoiceRoomCommunity($room, $receiverId);
        }

        $invitation = RoomInvitation::create([
            'room_id'       => $roomId,
            'receiver_id'   => $receiverId,
            'sender_id'     => $sender->id,
            'type'          => $type,
            'status'        => Consts::ROOM_INVITATION_STATUS_CREATED
        ]);

        $invitation->room = $room;
        $invitation->sender_username = $sender->username;

        event(new RoomUserInvited($receiverId, $invitation));

        $this->sendInviteNotification($room, $invitation);

        return true;
    }

    private function sendInviteNotification($room, $invitation)
    {
        $notificationParams = [
            'user_id' => $invitation->receiver_id,
            'type' => Consts::NOTIFY_TYPE_VOICE_ROOM_INVITATION,
            'message' => Consts::MESSAGE_NOTIFY_ROOM_INVITATION,
            'props' => [
                'room_category' => $room->game_id,
                'room_title' => $room->title
            ],
            'data' => [
                'user'          => (object) ['id' => $invitation->sender_id],
                'room_name'     => $room->name,
                'invitation_id' => $invitation->id
            ]
        ];
        $this->fireNotification(Consts::NOTIFY_TYPE_VOICE_ROOM, $notificationParams);
    }

    public function kickUserOutRoom($userId, $roomId)
    {
        $voiceChatRoomUser = VoiceChatRoomUser::where('room_id', $roomId)
            ->where('user_id', $userId)
            ->whereNull('ended_time')
            ->where('type', '!=', Consts::ROOM_USER_TYPE_HOST)
            ->first();
        if (!$voiceChatRoomUser) {
            throw new InvalidActionException();
        }

        $eliminatorId = Auth::id();
        $mod = VoiceChatRoomUser::where('room_id', $roomId)
            ->where('user_id', $eliminatorId)
            ->whereNull('ended_time')
            ->when(
                $voiceChatRoomUser->type === Consts::ROOM_USER_TYPE_MODERATOR,
                function ($query) {
                    $query->where('type', Consts::ROOM_USER_TYPE_HOST);
                },
                function ($query) {
                    $query->whereIn('type', [Consts::ROOM_USER_TYPE_HOST, Consts::ROOM_USER_TYPE_MODERATOR]);
                }
            )
            ->exists();
        if (!$mod) {
            throw new VoiceGroupException('exceptions.not_permission_kick_user');
        }

        $voiceChatRoomUser->is_kicked = Consts::TRUE;
        $voiceChatRoomUser->eliminator_id = $eliminatorId;
        $voiceChatRoomUser->ended_time = now();
        $voiceChatRoomUser->save();

        $this->updateRaiseHand($roomId, $userId, Consts::ROOM_REQUEST_STATUS_CANCELED);

        $voiceChatRoomUser->user = $this->getUser($voiceChatRoomUser->user_id);
        event(new RoomUserKicked($voiceChatRoomUser));

        CalculateRoomCurrentSize::dispatch($roomId)->onQueue(Consts::QUEUE_VOICE_GROUP);
        CalculateRoomCategoryStatistic::dispatch($voiceChatRoomUser->voiceChatRoom->game_id)->onQueue(Consts::QUEUE_VOICE_GROUP);

        return $voiceChatRoomUser;
    }

    public function raiseHand($data)
    {
        $userId = Auth::id();
        $roomRequest = RoomRequest::where('user_id', $userId)
            ->where('room_id', $data['room_id'])
            ->where('status', Consts::ROOM_REQUEST_STATUS_CREATED)
            ->first();
        if ($roomRequest) {
            throw new InvalidActionException();
        }

        $roomRequest = RoomRequest::create([
            'user_id'       => $userId,
            'room_id'       => $data['room_id'],
            'status'        => Consts::ROOM_REQUEST_STATUS_CREATED
        ]);

        $roomRequest->user = $this->getUser($userId);
        event(new RoomUserHandRaised($roomRequest));

        return $roomRequest;
    }

    public function listRaiseHand($roomId)
    {
        return RoomRequest::with(['user'])
            ->where('room_id', $roomId)
            ->where('status', Consts::ROOM_REQUEST_STATUS_CREATED)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    private function updateRaiseHand($roomId, $userId, $status = Consts::ROOM_REQUEST_STATUS_ACCEPTED)
    {
        $roomRequest = RoomRequest::where('user_id', $userId)
            ->where('room_id', $roomId)
            ->where('status', Consts::ROOM_REQUEST_STATUS_CREATED)
            ->first();
        if (!$roomRequest) {
            return;
        }

        $roomRequest->status = $status;
        $roomRequest->save();

        return $roomRequest;
    }

    public function listVoiceCategory($params)
    {
        return RoomCategory::when(!empty(array_get($params, 'game_id')), function ($query) use ($params) {
                $query->where('game_id', array_get($params, 'game_id'));
            })
            ->orderBy('pinned', 'desc')
            ->orderBy('total_room', 'desc')
            ->orderBy('total_user', 'desc')
            ->paginate(array_get($params, 'limit', Consts::DEFAULT_PER_PAGE));
    }

    public function listVoiceChatRoom($params)
    {
        $rooms = VoiceChatRoom::with(['host'])
            ->where('status', Consts::VOICE_ROOM_STATUS_CALLING)
            ->where('is_private', Consts::FALSE)
            ->when(!empty(array_get($params, 'game_id')), function ($query) use ($params) {
                $query->where('game_id', array_get($params, 'game_id'));
            })
            ->when(!empty(array_get($params, 'type')), function ($query) use ($params) {
                $query->where('type', array_get($params, 'type'));
            })
            ->orderBy('pinned', 'desc')
            ->orderBy('current_size', 'desc')
            ->paginate(array_get($params, 'limit', Consts::DEFAULT_PER_PAGE));

        $totalUsers = VoiceChatRoomUser::join('voice_chat_rooms', 'voice_chat_rooms.id', 'voice_chat_room_users.room_id')
            ->where('voice_chat_rooms.status', Consts::VOICE_ROOM_STATUS_CALLING)
            ->where('voice_chat_rooms.is_private', Consts::FALSE)
            ->whereNull('voice_chat_room_users.ended_time')
            ->when(!empty(array_get($params, 'type')), function ($query) use ($params) {
                $query->where('voice_chat_rooms.type', array_get($params, 'type'));
            })
            ->when(!empty(array_get($params, 'game_id')), function ($query) use ($params) {
                $query->where('voice_chat_rooms.game_id', array_get($params, 'game_id'));
            })
            ->count();

        $rooms = VoiceGroupUtils::mergeShareVideo($rooms);

        $custom = collect(['total_users' => $totalUsers]);

        $rooms = $custom->merge($rooms);

        return $rooms;
    }

    private function getRoomTotalDetail($roomName)
    {
        $room = $this->getRoomDetail($roomName);
        $room->users = $this->getRoomUsers($roomName);
        return $room;
    }

    public function getRoomDetail($roomName)
    {
        $room = VoiceChatRoom::with([
                'host',
                'raiseHands',
                'category',
                'community'
            ])
            ->leftJoin('room_settings', 'room_settings.room_id', 'voice_chat_rooms.id')
            ->select('voice_chat_rooms.*', 'room_settings.allow_ask_question')
            ->where('name', $roomName)
            ->where('status', Consts::VOICE_ROOM_STATUS_CALLING)
            ->first();

        if (!$room) {
            return null;
        }

        $room->raised_hand_users = $room->raiseHands->transform(function ($item, $key) {
            return $item->user_id;
        });

        unset($room->raiseHands);

        return $room;
    }

    public function getRoomUsers($roomName, $params = [])
    {
        $room = VoiceChatRoom::where('name', $roomName)
            ->where('status', Consts::VOICE_ROOM_STATUS_CALLING)
            ->first();
        if (!$room) {
            return [];
        }

        $users = VoiceChatRoomUser::where('room_id', $room->id)
            ->whereNull('ended_time')
            ->with(['user'])
            ->when(array_get($params, 'type'), function ($query) use ($params) {
                $query->where('type', array_get($params, 'type'));
            });

        if (array_get($params, 'limit')) {
            return $users->paginate(array_get($params, 'limit'));
        }
        return $users->get();
    }

    private function getUser($userId)
    {
        $user = DB::table('users')->join('user_settings', 'user_settings.id', 'users.id')
            ->where('users.id', $userId)
            ->select('users.id', 'users.avatar', 'users.sex', 'users.username', 'users.user_type', 'users.is_vip', 'user_settings.online as online_setting')
            ->first();
        return (object) $user;
    }

    public function updateUserUsername($roomId, $username)
    {
        $voiceChatRoomUser = VoiceChatRoomUser::where('room_id', $roomId)
            ->where('user_id', Auth::id())
            ->whereNull('ended_time')
            ->first();
        if (!$voiceChatRoomUser) {
            throw new InvalidActionException();
        }

        $voiceChatRoomUser->username = $username;
        $voiceChatRoomUser->save();

        $voiceChatRoomUser->user = $this->getUser($voiceChatRoomUser->user_id);
        event(new UsernameInRoomUpdated($voiceChatRoomUser));

        return $voiceChatRoomUser;
    }

    public function getInviteList($roomId, $params)
    {
        $exceptUsers = VoiceChatRoomUser::where('room_id', $roomId)
            ->whereNull('ended_time')
            ->pluck('user_id')
            ->toArray();

        $friends = $this->userService->getListFriend($exceptUsers, $params);
        $invitationUsers = RoomInvitation::where('room_id', $roomId)
            ->where('sender_id', Auth::id())
            ->whereIn('receiver_id', $friends->pluck('id'))
            ->pluck('receiver_id');

        $friends->transform(function ($item) use ($invitationUsers) {
            $item->invited = in_array($item->id, $invitationUsers->toArray());
            return $item;
        });

        return $friends;
    }

    public function getCurrentRoom()
    {
        return VoiceChatRoomUser::join('voice_chat_rooms', 'voice_chat_rooms.id', 'voice_chat_room_users.room_id')
            ->where('voice_chat_room_users.user_id', Auth::id())
            ->whereNull('voice_chat_room_users.ended_time')
            ->where('voice_chat_rooms.status', Consts::VOICE_ROOM_STATUS_CALLING)
            ->first();
    }

    public function checkRandomRoom($gameId, $params)
    {
        return $this->checkNextRoom($gameId, $params, true);
    }

    public function checkNextRoom($gameId, $params, $includeCurrent = false)
    {
        $category = MasterdataService::getOneTable('room_categories')
            ->where('game_id', $gameId)
            ->first();
        if (!$category) {
            throw new VoiceGroupException('exceptions.invalid_category');
        }

        $availableRooms = $this->tryGettingAvailableRooms($category->game_id, $params, $includeCurrent);
        if ($availableRooms->isEmpty()) {
            throw new VoiceGroupException('exceptions.no_available_room');
        }

        // get from cache.
        list($cacheKey, $roomIds, $sid) = $this->getRoomFromCache($category);

        // ignore old candicate.
        $data = collect($availableRooms)->filter(function ($item) use ($roomIds) {
            return !in_array($item['id'], $roomIds);
        });

        if ($data->isEmpty()) {
            $data = $availableRooms;
            $roomIds = [];
        }

        $room = $data->first();
        $roomIds[] = $room->id;

        $currentRoom = $this->getCurrentRoom();
        if (!$includeCurrent && $currentRoom) {
            $this->leaveVoiceChatRoom($currentRoom->room_id, Auth::id(), true);
        }

        $key = sprintf('%s-%s', $cacheKey, time());
        $sid = $sid ?? gamelancer_hash($key);

        Cache::put(
            $cacheKey,
            ['sid' => $sid, 'data' => $roomIds],
            static::USER_RANDOM_ROOM_TIME_LIVE
        );

        return $room;
    }

    private function tryGettingAvailableRooms($gameId, $params, $includeCurrent)
    {
        $gameChattingInfo = Game::where('type', Consts::CATEGORY_TYPE_CHAT)->first();
        $gameChattingId = $gameChattingInfo->id;
        $currentRoom = $this->getCurrentRoom();
        $type = array_get($params, 'type', $currentRoom ? $currentRoom->type : null);
        $communityId = array_get($params, 'community_id');
        $kickedRooms = VoiceChatRoomUser::where('user_id', Auth::id())
            ->where('is_kicked', Consts::TRUE)
            ->pluck('room_id');

        return VoiceChatRoom::withCount(['voiceChatRoomUser'])
            ->when(!$includeCurrent && $currentRoom, function ($query) use ($currentRoom) {
                $query->where('id', '!=', $currentRoom->room_id);
            })
            ->when($type, function ($query) use ($type) {
                $query->where('type', $type);
            })
            ->when($gameId === $gameChattingId && $communityId, function ($query) use ($communityId) {
                $query->where('community_id', $communityId);
            })
            ->where('game_id', $gameId)
            ->where('status', Consts::VOICE_ROOM_STATUS_CALLING)
            ->where('is_private', Consts::FALSE)
            ->whereNotIn('id', $kickedRooms)
            ->get()
            ->map(function ($item) {
                $item->available_slots = $item->size - $item->voice_chat_room_user_count;
                return $item;
            })
            ->filter(function ($item, $key) {
                return $item->available_slots > 0;
            })
            ->sortBy('available_slots');
    }

    private function getRoomFromCache($category)
    {
        $userId     = Auth::id();
        $cacheKey   = "user-{$userId}-game-{$category->game_id}-room";
        $cacheData  = Cache::get($cacheKey) ?? [];
        $sid        = array_get($cacheData, 'sid');
        $data       = array_get($cacheData, 'data', []);

        return [$cacheKey, $data, $sid];
    }

    public function reportRoom($roomId, $params)
    {
        $room = VoiceChatRoom::where('id', $roomId)
            ->where('status', Consts::VOICE_ROOM_STATUS_CALLING)
            ->exists();
        if (!$room) {
            throw new VoiceGroupException('exceptions.room_not_existed');
        }

        $reporterId = Auth::id();
        $reporter = VoiceChatRoomUser::where('room_id', $roomId)
            ->where('user_id', $reporterId)
            ->whereNull('ended_time')
            ->exists();
        if (!$reporter) {
            throw new VoiceGroupException('exceptions.not_in_room');
        }

        if ($this->checkRoomReportExisted($roomId)) {
            throw new VoiceGroupException('exceptions.already_report_room');
        }

        $report = RoomReport::create([
            'room_id'       => $roomId,
            'reporter_id'   => $reporterId,
            'reason_id'     => array_get($params, 'reason_id'),
            'details'       => array_get($params, 'details'),
            'status'        => Consts::REPORT_STATUS_PROCESSING
        ]);

        return $report;
    }

    public function checkRoomReportExisted($roomId)
    {
        return RoomReport::where('room_id', $roomId)
            ->where('reporter_id', Auth::id())
            ->where('status', Consts::REPORT_STATUS_PROCESSING)
            ->exists();
    }

    public function reportUser($roomId, $params)
    {
        $room = VoiceChatRoom::where('id', $roomId)
            ->where('status', Consts::VOICE_ROOM_STATUS_CALLING)
            ->exists();
        if (!$room) {
            throw new VoiceGroupException('exceptions.room_not_existed');
        }

        $reporterId = Auth::id();
        $reporter = VoiceChatRoomUser::where('room_id', $roomId)
            ->where('user_id', $reporterId)
            ->whereNull('ended_time')
            ->exists();
        if (!$reporter) {
            throw new VoiceGroupException('exceptions.not_in_room');
        }

        $reportedId = array_get($params, 'reported_user');
        $reportedUser = VoiceChatRoomUser::where('room_id', $roomId)
            ->where('user_id', $reportedId)
            ->whereNull('ended_time')
            ->exists();
        if (!$reportedUser) {
            throw new VoiceGroupException('exceptions.user_not_in_room');
        }

        if ($this->checkUserReportExisted($roomId, $reportedId)) {
            throw new VoiceGroupException('exceptions.already_report_user');
        }

        $report = RoomUserReport::create([
            'room_id'           => $roomId,
            'reporter_id'       => $reporterId,
            'reported_user_id'  => $reportedId,
            'reason_id'         => array_get($params, 'reason_id'),
            'details'           => array_get($params, 'details'),
            'status'            => Consts::REPORT_STATUS_PROCESSING
        ]);

        return $report;
    }

    public function checkUserReportExisted($roomId, $reportedUser)
    {
        return RoomUserReport::where('room_id', $roomId)
            ->where('reporter_id', Auth::id())
            ->where('reported_user_id', $reportedUser)
            ->where('status', Consts::REPORT_STATUS_PROCESSING)
            ->exists();
    }

    private function fireEventVoiceChatRoomUpdated($roomId)
    {
        $room = VoiceChatRoom::with(['host'])
            ->where('id', $roomId)
            ->where('status', Consts::VOICE_ROOM_STATUS_CALLING)
            ->first();

        $room->video = VoiceGroupUtils::getShareVideoByRoomId($roomId);

        event(new VoiceChatRoomUpdated($room));

        if ($room->type === Consts::ROOM_TYPE_COMMUNITY) {
            CalculateCommunityRoomStatistic::dispatch($room->community_id)->onQueue(Consts::QUEUE_COMMUNITY);
        }
    }

    private function fireEventVoiceCategoryUpdated($gameId)
    {
        $roomCategory = RoomCategory::where('game_id', $gameId)->first();

        event(new VoiceCategoryUpdated($roomCategory));
    }

    public function askQuestion($params)
    {
        $settings = RoomSetting::where('room_id', array_get($params, 'room_id'))
            ->value('allow_ask_question');
        if (!$settings) {
            throw new VoiceGroupException('exceptions.feature_turn_off');
        }

        $question = RoomQuestion::create([
            'room_id' => array_get($params, 'room_id'),
            'user_id' => Auth::id(),
            'question' => array_get($params, 'question'),
            'status' => Consts::ROOM_QUESTION_STATUS_PENDING
        ]);

        $question->user = $this->getUser($question->user_id);
        event(new RoomQuestionAsked($question));
        CalculateAmaRoomQuestions::dispatch(array_get($params, 'room_id'))->onQueue(Consts::QUEUE_VOICE_GROUP);
        return $question;
    }

    public function getRoomQuestions($roomId, $params)
    {
        $data = RoomQuestion::with(['user'])
            ->where('room_id', $roomId)
            ->orderBy('created_at', 'asc')
            ->when(array_get($params, 'include_answering'), function ($query) use ($params) {
                return $query->whereIn('status', [Consts::ROOM_QUESTION_STATUS_PENDING, Consts::ROOM_QUESTION_STATUS_ANSWERING])
                    ->get();
            }, function ($query) use ($params) {
                return $query->where('status', Consts::ROOM_QUESTION_STATUS_PENDING)
                    ->paginate(array_get($params, 'limit', Consts::DEFAULT_PER_PAGE));
            });

        return $data;
    }

    public function rejectQuestion($roomId, $questionId)
    {
        $rejector = VoiceChatRoomUser::where('room_id', $roomId)
            ->where('user_id', Auth::id())
            ->whereIn('type', [Consts::ROOM_USER_TYPE_HOST, Consts::ROOM_USER_TYPE_MODERATOR])
            ->whereNull('ended_time')
            ->first();

        if (!$rejector) {
            throw new VoiceGroupException('exceptions.not_permission_reject_question');
        }

        $question = RoomQuestion::where('id', $questionId)
            ->where('room_id', $roomId)
            ->first();
        $question->status = Consts::ROOM_QUESTION_STATUS_REJECTED;
        $question->rejector_id = $rejector->user_id;
        $question->save();

        $question->rejector = User::where('id', $question->rejector_id)->value('username');
        $question->user = $this->getUser($question->user_id);
        event(new RoomQuestionRejected($question));
        CalculateAmaRoomQuestions::dispatch($roomId)->onQueue(Consts::QUEUE_VOICE_GROUP);
        return $question;
    }

    public function acceptQuestion($roomId, $questionId)
    {
        $acceptor = VoiceChatRoomUser::where('room_id', $roomId)
            ->where('user_id', Auth::id())
            ->whereIn('type', [Consts::ROOM_USER_TYPE_HOST, Consts::ROOM_USER_TYPE_MODERATOR])
            ->whereNull('ended_time')
            ->first();

        if (!$acceptor) {
            throw new VoiceGroupException('exceptions.not_permission_accept_question');
        }

        $currentAnsweringQuestion = $this->closeCurrentQuestion($roomId);

        $question = RoomQuestion::where('id', $questionId)
            ->where('room_id', $roomId)
            ->first();
        $question->status = Consts::ROOM_QUESTION_STATUS_ANSWERING;
        $question->acceptor_id = $acceptor->user_id;
        $question->save();

        $question->user = $this->getUser($question->user_id);
        $question->acceptor = User::where('id', $question->acceptor_id)->value('username');

        event(new RoomQuestionAccepted($question));

        return $question;
    }

    private function closeCurrentQuestion($roomId)
    {
        $question = RoomQuestion::where('room_id', $roomId)
            ->where('status', Consts::ROOM_QUESTION_STATUS_ANSWERING)
            ->first();

        if ($question) {
            $question->status = Consts::ROOM_QUESTION_STATUS_ANSWERED;
            $question->save();

            event(new RoomQuestionAnswered($question));
        }

        return $question;
    }

    public function switchAllowQuestion($roomId, $allowQuestion)
    {
        $executor = VoiceChatRoomUser::where('room_id', $roomId)
            ->where('user_id', Auth::id())
            ->whereIn('type', [Consts::ROOM_USER_TYPE_HOST, Consts::ROOM_USER_TYPE_MODERATOR])
            ->whereNull('ended_time')
            ->first();

        if (!$executor) {
            throw new VoiceGroupException('exceptions.not_permission');
        }

        $settings = RoomSetting::firstOrNew(['room_id' => $roomId]);
        $settings->allow_ask_question = $allowQuestion ? Consts::TRUE : Consts::FALSE;
        $settings->save();

        event(new RoomSettingChanged($settings));

        return $settings;
    }

    public function checkCategoryExisted($slug)
    {
        $gameChattingInfo = Game::where('type', Consts::CATEGORY_TYPE_CHAT)->first();
        if ($slug === Consts::CHATTING_ROOM_CATEGORY_GAME_SLUG) {
            $category = RoomCategory::with(['game'])
                ->where('game_id', $gameChattingInfo->id)->where('type', Consts::CATEGORY_TYPE_CHAT)->first();
        } else {
            $category = RoomCategory::with(['game'])
                ->join('games', 'games.id', 'room_categories.game_id')
                ->select('room_categories.*')
                ->where('games.slug', $slug)
                ->first();
        }

        if (!$category) {
            throw new VoiceGroupException('exceptions.voice_category_not_existed');
        }

        return $category;
    }

    public function shareVideo($roomId, $video)
    {
        $room = VoiceChatRoom::where('id', $roomId)
            ->where('status', Consts::VOICE_ROOM_STATUS_CALLING)
            ->exists();
        if (!$room) {
            throw new VoiceGroupException('exceptions.room_not_existed');
        }

        $host = VoiceChatRoomUser::where('room_id', $roomId)
            ->where('type', Consts::ROOM_USER_TYPE_HOST)
            ->where('user_id', Auth::id())
            ->whereNull('ended_time')
            ->first();
        if (!$host) {
            throw new InvalidActionException();
        }

        VoiceGroupUtils::saveShareVideoToCache($roomId, $video);

        $this->fireEventVoiceChatRoomUpdated($roomId);
    }

    public function clearShareVideo($roomId, $userId = null)
    {
        $room = VoiceChatRoom::where('id', $roomId)
            ->where('status', Consts::VOICE_ROOM_STATUS_CALLING)
            ->exists();
        if (!$room) {
            if ($userId) {
                return;
            }
            throw new VoiceGroupException('exceptions.room_not_existed');
        }

        $host = VoiceChatRoomUser::where('room_id', $roomId)
            ->where('type', Consts::ROOM_USER_TYPE_HOST)
            ->where('user_id', $userId ?: Auth::id())
            ->whereNull('ended_time')
            ->first();
        if (!$host) {
            throw new InvalidActionException();
        }

        VoiceGroupUtils::clearShareVideo($roomId);

        $this->fireEventVoiceChatRoomUpdated($roomId);
    }

    // ======================== API VERSION 2 ========================

    public function createChannelV2($username)
    {
        $oppsiteUser = User::where('username', $username)->first();

        if (!$oppsiteUser) {
            $this->throwUserInvalid($username);
        }

        $this->validate($oppsiteUser);

        $channel = Channel::getChatChannel($oppsiteUser->id);

        $channelBlock = ChannelMember::where('channel_id', $channel->id)
            ->where('is_blocked', Consts::TRUE)
            ->exists();

        if ($channelBlock) {
            throw new ChannelBlockedException();
        }

        $followEachOther = UserFollowing::userHasFollowerByFollowingId(Auth::id(), $oppsiteUser->id)
            && UserFollowing::userHasFollowerByFollowingId($oppsiteUser->id, Auth::id());

        $chatEachOther = ChannelMember::userHasSendedMessgae($channel->id, Auth::id())
            && ChannelMember::userHasSendedMessgae($channel->id, $oppsiteUser->id);

        if (!$followEachOther && !$chatEachOther) {
            $this->throwFollowOrChatInvalid();
        }

        $currentMilis = time();
        $hash = gamelancer_hash("$channel->channel_tag_{$currentMilis}");

        VoiceDiary::create([
            'channel_id'    => $channel->id,
            'hash'          => $hash,
            'caller_id'     => Auth::id(),
            'receiver_id'   => $channel->getOppositeUserId(),
            'status'        => Consts::VOICE_STATUS_CREATED
        ]);

        return $hash;
    }

    // Voice Group
    public function createVoiceChatRoomV2($params)
    {
        $this->checkUserInOtherRoom();

        $user = User::withoutAppends()
            ->leftJoin('voice_group_managers', 'voice_group_managers.user_id', 'users.id')
            ->select('users.id', 'users.username', 'users.sex', 'users.avatar',
                DB::raw('(CASE WHEN voice_group_managers.deleted_at IS NULL THEN voice_group_managers.role ELSE NULL END) AS voice_group_role'))
            ->where('users.id', Auth::id())
            ->first();

        $currentMilis = time();
        $username = $user->username;
        $hash = gamelancer_hash("{$username}_{$currentMilis}");

        $params = array_filter($params);
        $isCommunityRoom = !!array_get($params, 'community_id');

        $type = $isCommunityRoom ? Consts::ROOM_TYPE_COMMUNITY : array_get($params, 'type', Consts::ROOM_TYPE_HANGOUT);
        $gameIdChatting = Game::where('type', Consts::CATEGORY_TYPE_CHAT)->first();
        $gameId = $isCommunityRoom ? $gameIdChatting->id : array_get($params, 'game_id');
        $pinned = $user->voice_group_role === Consts::VOICE_GROUP_ROLE_ADMIN && !$isCommunityRoom
            ? Consts::TRUE : Consts::FALSE;

        $title = array_get($params, 'title') ? $params['title'] : VoiceGroupUtils::buildTitle($gameId, $type);

        $room = VoiceChatRoom::create([
            'user_id'           => $user->id,
            'creator_id'        => $user->id,
            'pinned'            => $pinned,
            'game_id'           => $gameId,
            'is_private'        => array_get($params, 'is_private', Consts::FALSE),
            'name'              => $hash,
            'title'             => $title,
            'topic'             => array_get($params, 'topic'),
            'type'              => $type,
            'size'              => $isCommunityRoom ? Consts::COMMUNITY_VOICE_ROOM_SIZE : array_get($params, 'size', 100),
            'current_size'      => 1,
            'code'              => array_get($params, 'code'),
            'rules'             => array_get($params, 'rules'),
            'background_url'    => array_get($params, 'background_url'),
            'status'            => Consts::VOICE_ROOM_STATUS_CALLING,
            'community_id'      => array_get($params, 'community_id')
        ]);

        $voiceChatRoomUser = $this->createVoiceChatRoomUser(
            $room,
            [
                'user_id'           => $user->id
            ],
            [
                'type'              => Consts::ROOM_USER_TYPE_HOST,
                'sid'               => array_get($params, 'sid'),
                'username'          => array_get($params, 'username'),
                'started_time'      => now()
            ]
        );

        if ($room->type === Consts::ROOM_TYPE_AMA) {
            $roomSettings = RoomSetting::create([
                'room_id' => $room->id,
                'allow_ask_question' => Consts::TRUE
            ]);

            $room->allow_ask_question = $roomSettings->allow_ask_question;
        }

        $rtcToken = Agora::generateRtcToken($hash, $user->id);
        $rtmToken = Agora::generateRtmToken(strval($user->id));

        $friendToInvite = array_get($params, 'friend_id');
        if ($friendToInvite) {
            $this->inviteUserIntoRoom(['room_id' => $room->id, 'user_id' => $friendToInvite]);
        }

        $room->user = $user;
        event(new VoiceChatRoomCreated($room));

        CalculateRoomCategoryStatistic::dispatch($room->game_id)->onQueue(Consts::QUEUE_VOICE_GROUP);

        $this->fireEventVoiceCategoryUpdated($room->game_id);

        if (!$room->is_private) {
            $this->sendNotificationToFriends($room, $friendToInvite);
        }

        return $room;
    }

    public function checkRoomAvailableV2($name)
    {
        $room = VoiceChatRoom::where('name', $name)->first();
        if ($room->status === Consts::VOICE_ROOM_STATUS_ENDED) {
            throw new VoiceGroupException('exceptions.room_ended');
        }

        $userId = Auth::id();
        $count = VoiceChatRoomUser::where('room_id', $room->id)
            ->where('user_id', '!=', $userId)
            ->whereNull('ended_time')
            ->count();

        if ($count >= $room->size) {
            throw new RoomMaxSizeException();
        }

        $roomUser = VoiceChatRoomUser::where('room_id', $room->id)
            ->where('user_id', $userId)
            ->where('is_kicked', Consts::TRUE)
            ->first();

        if ($roomUser) {
            throw new UserHaveKickedFromRoomException();
        }

        $communityService = new CommunityService();
        $isInCommunity = $communityService->checkMemberExists($room->community_id, Auth::id());
        if ($room->type === Consts::ROOM_TYPE_COMMUNITY && !$isInCommunity) {
            throw new VoiceGroupException('exceptions.user_not_in_community');
        }

        return $room;
    }

    public function joinVoiceChatRoomV2($name, $params = [])
    {
        $this->checkRoomAvailableV2($name);
        $room = $this->checkUserCanJoinRoom($name, $params);

        $userId = Auth::id();

        $roomUser = VoiceChatRoomUser::where('room_id', $room->id)
            ->where('user_id', $userId)
            ->whereNull('ended_time')
            ->when(array_get($params, 'sid'), function ($query) use ($params) {
                $query->where('sid', array_get($params, 'sid'));
            })
            ->first();

        $currentMilis = time();
        $hashSid = gamelancer_hash("{$room->id}-{$userId}-{$currentMilis}");
        $sid = array_get($params, 'sid') ?: $hashSid;

        if (empty($roomUser)) {
            $roomUser = $this->createNewRoomUserV2($room, $sid, $params);
        } else {
            $roomUser->sid = $sid;
            $roomUser->save();
        }

        $rtcToken = Agora::generateRtcToken($room->name, $userId);
        $rtmToken = Agora::generateRtmToken(strval($userId));

        $this->fireEventVoiceChatRoomUpdated($room->id);
        $this->fireEventVoiceCategoryUpdated($room->game_id);

        return [
            'rtc_token'             => $rtcToken,
            'rtm_token'             => $rtmToken,
            'room'                  => $this->getRoomTotalDetail($name),
            'room_user'             => $roomUser,
            'sid'                   => $roomUser->sid
        ];
    }

    public function forceJoinRoomV2($name, $params)
    {
        $userId = Auth::id();
        $room = $this->checkRoomAvailable($name);
        $currentRoomUser = $this->getCurrentRoom();

        if (!$currentRoomUser) {
            return $this->joinVoiceChatRoomV2($name, $params);
        }

        if ($room->id !== $currentRoomUser->room_id) {
            $this->leaveVoiceChatRoom($currentRoomUser->room_id);
            return $this->joinVoiceChatRoomV2($name, $params);
        }

        $currentMilis = time();
        $hashSid = gamelancer_hash("{$room->id}-{$userId}-{$currentMilis}");
        $sid = array_get($params, 'sid') ?: $hashSid;

        $roomUser = VoiceChatRoomUser::where('room_id', $currentRoomUser->room_id)
            ->where('user_id', $userId)
            ->whereNull('ended_time')
            ->first();
        $roomUser->sid = $sid;
        $roomUser->save();

        $rtcToken = Agora::generateRtcToken($room->name, $userId);
        $rtmToken = Agora::generateRtmToken(strval($userId));

        event(new UserLeftRoomFromOtherPlatforms($roomUser));

        return [
            'rtc_token'             => $rtcToken,
            'rtm_token'             => $rtmToken,
            'room'                  => $this->getRoomTotalDetail($name),
            'room_user'             => $roomUser,
            'sid'                   => $roomUser->sid
        ];
    }

    private function createNewRoomUserV2($room, $sid, $params)
    {
        $userId = Auth::id();

        $invitation = RoomInvitation::when(array_get($params, 'invitation_id'), function ($query) use ($params) {
            $query->where('id', array_get($params, 'invitation_id'));
        })->where('receiver_id', $userId)->where('room_id', $room->id)->first();

        $roomUser = $this->createVoiceChatRoomUser(
            $room,
            [
                'user_id'           => $userId
            ],
            [
                'invited_user_id'   => $invitation ? $invitation->sender_id : null,
                'type'              => $this->getRoomJoinedUserType($room, $params, $invitation),
                'sid'               => $sid,
                'username'          => array_get($params, 'username'),
                'started_time'      => now()
            ]
        );
        $roomUser->user = $this->getUser($roomUser->user_id);
        $roomUser->invited_user = $invitation ? $this->getUser($invitation->sender_id) : null;

        event(new RoomUserJoined($roomUser));

        CalculateRoomCurrentSize::dispatch($room->id)->onQueue(Consts::QUEUE_VOICE_GROUP);
        CalculateRoomCategoryStatistic::dispatch($room->game_id)->onQueue(Consts::QUEUE_VOICE_GROUP);
        return $roomUser;
    }

    private function getRoomJoinedUserType($room, $params, $invitation = null)
    {
        if ($invitation) {
            return $invitation->type;
        }

        if ($room->type === Consts::ROOM_TYPE_COMMUNITY) {
            return $this->getUserTypeVoiceRoomCommunity($room, null);
        }

        return $room->type === Consts::ROOM_TYPE_PLAY
            ? Consts::ROOM_USER_TYPE_SPEAKER
            : Consts::ROOM_USER_TYPE_GUEST;
    }

    public function makeHostV2($userId, $roomId)
    {
        $newHost = VoiceChatRoomUser::where('room_id', $roomId)
            ->where('user_id', $userId)
            ->whereIn('type', [Consts::ROOM_USER_TYPE_SPEAKER, Consts::ROOM_USER_TYPE_MODERATOR])
            ->whereNull('ended_time')
            ->first();
        if (!$newHost) {
            throw new InvalidActionException();
        }

        $oldHost = VoiceChatRoomUser::where('room_id', $roomId)
            ->where('user_id', Auth::id())
            ->whereNull('ended_time')
            ->where('type', Consts::ROOM_USER_TYPE_HOST)
            ->first();
        if (!$oldHost) {
            throw new VoiceGroupException('exceptions.not_permission_make_host');
        }

        $newHost->type = Consts::ROOM_USER_TYPE_HOST;
        $newHost->save();

        $this->updateRaiseHand($roomId, $userId);

        $newHost->user = $this->getUser($newHost->user_id);

        event(new RoomHostUpgraded($newHost));

        return $newHost;
    }

    public function makeModeratorV2($userId, $roomId)
    {
        $roomUser = VoiceChatRoomUser::where('room_id', $roomId)
            ->where('user_id', $userId)
            ->where('type', Consts::ROOM_USER_TYPE_SPEAKER)
            ->whereNull('ended_time')
            ->first();
        if (!$roomUser) {
            throw new InvalidActionException();
        }

        $host = VoiceChatRoomUser::where('room_id', $roomId)
            ->where('user_id', Auth::id())
            ->whereNull('ended_time')
            ->where('type', Consts::ROOM_USER_TYPE_HOST)
            ->exists();
        if (!$host) {
            throw new VoiceGroupException('exceptions.not_permission_make_moderator');
        }

        $roomUser->type = Consts::ROOM_USER_TYPE_MODERATOR;
        $roomUser->save();

        $this->updateRaiseHand($roomId, $userId);

        $roomUser->user = $this->getUser($roomUser->user_id);
        event(new ModeratorUpgraded($roomUser));

        return $roomUser;
    }

    private function transferOwnerIfNeedV2($room)
    {
        $host = VoiceChatRoomUser::where('room_id', $room->id)
            ->whereNull('ended_time')
            ->where('type', Consts::ROOM_USER_TYPE_HOST)
            ->orderBy('started_time')
            ->first();
        if ($host) {
            $room->user_id = $host->user_id;
            $room->save();
            return;
        }

        $voiceChatRoomUser = VoiceChatRoomUser::where('room_id', $room->id)
            ->whereNull('ended_time')
            ->when($room->type !== Consts::ROOM_TYPE_PLAY,
                function ($query) {
                    $queryOrder = "CASE WHEN type = '" . Consts::ROOM_USER_TYPE_MODERATOR ."' THEN 1 ";
                    $queryOrder .= "WHEN type = '" . Consts::ROOM_USER_TYPE_SPEAKER ."' THEN 2 ";
                    $queryOrder .= "ELSE 3 END";

                    $query->whereIn('type', [Consts::ROOM_USER_TYPE_MODERATOR, Consts::ROOM_USER_TYPE_SPEAKER])
                        ->orderByRaw($queryOrder);
                },
                function ($query) {
                    $query->where('type', Consts::ROOM_USER_TYPE_SPEAKER);
                }
            )
            ->first();

        if (!$voiceChatRoomUser) {
            return;
        }

        $voiceChatRoomUser->type = Consts::ROOM_USER_TYPE_HOST;
        $voiceChatRoomUser->save();

        $room->user_id = $voiceChatRoomUser->user_id;
        $room->save();

        $voiceChatRoomUser->user = $this->getUser($voiceChatRoomUser->user_id);
        event(new RoomHostUpgraded($voiceChatRoomUser));

        return $voiceChatRoomUser;
    }

    public function listVoiceChatRoomV2($params)
    {
        $queryOrder = "CASE WHEN size > current_size THEN size - current_size ELSE 9999 END";
        $rooms = VoiceChatRoom::with(['host'])
            ->where('status', Consts::VOICE_ROOM_STATUS_CALLING)
            ->where('is_private', Consts::FALSE)
            ->when(empty(array_get($params, 'community_id')), function ($query) use ($params) {
                $query->when(!empty(array_get($params, 'game_id')), function ($query2) use ($params) {
                    $query2->where('game_id', array_get($params, 'game_id'));
                })
                    ->when(!empty(array_get($params, 'type')), function ($query2) use ($params) {
                        $query2->where('type', array_get($params, 'type'));
                    })
                    ->whereNotIn('type', [Consts::ROOM_TYPE_COMMUNITY]);
            }, function ($query) use ($params) {
                // voice group for group community
                $query->where('community_id', array_get($params, 'community_id'));
            })
            ->orderBy('pinned', 'desc')
            ->orderByRaw($queryOrder)
            ->paginate(array_get($params, 'limit', Consts::DEFAULT_PER_PAGE));

        $totalUsers = VoiceChatRoomUser::join('voice_chat_rooms', 'voice_chat_rooms.id', 'voice_chat_room_users.room_id')
            ->where('voice_chat_rooms.status', Consts::VOICE_ROOM_STATUS_CALLING)
            ->where('voice_chat_rooms.is_private', Consts::FALSE)
            ->whereNull('voice_chat_room_users.ended_time')
            ->when(empty(array_get($params, 'community_id')), function ($query) use ($params) {
                $query->when(!empty(array_get($params, 'type')), function ($query2) use ($params) {
                    $query2->where('voice_chat_rooms.type', array_get($params, 'type'));
                })
                    ->when(!empty(array_get($params, 'game_id')), function ($query2) use ($params) {
                        $query2->where('voice_chat_rooms.game_id', array_get($params, 'game_id'));
                    });
            }, function ($query) use ($params) {
                // voice group for group community
                $query->where('voice_chat_rooms.community_id', array_get($params, 'community_id'));
            })
            ->count();

        $rooms = VoiceGroupUtils::mergeShareVideo($rooms);

        $custom = collect(['total_users' => $totalUsers]);

        $rooms = $custom->merge($rooms);

        return $rooms;
    }

    public function getInviteListV2($roomId, $params)
    {
        $room = VoiceChatRoom::where('id', $roomId)->first();

        $exceptUsers = VoiceChatRoomUser::where('room_id', $roomId)
            ->whereNull('ended_time')
            ->pluck('user_id')
            ->toArray();

        $friends = $this->userService->getListFriend($exceptUsers, $params);
        $communityMembers = CommunityMember::where('community_id', $room->community_id)->pluck('user_id');
        if ($room->community_id && !empty($communityMembers)) {
            $friends = $friends->filter(function ($item) use ($communityMembers) {
                return in_array($item->id, $communityMembers->toArray());
            })->values();
        }

        $invitatedUsers = RoomInvitation::where('room_id', $roomId)
            ->where('sender_id', Auth::id())
            ->whereIn('receiver_id', $friends->pluck('id'))
            ->where('status', Consts::ROOM_INVITATION_STATUS_CREATED)
            ->pluck('receiver_id');
        $friends->transform(function ($item) use ($invitatedUsers) {
            $item->invited = in_array($item->id, $invitatedUsers->toArray());
            return $item;
        });

        return $friends;
    }

    public function forceCreateRoomV2($params)
    {
        $curentRoom = $this->getCurrentRoom();
        if ($curentRoom->room_id) {
            $this->leaveVoiceChatRoom($curentRoom->room_id);
        }

        return $this->createVoiceChatRoomV2($params);
    }

    public function closeRoomCommunity($roomId)
    {
        $voiceChatRoom = VoiceChatRoom::where('id', $roomId)
            ->where('status', Consts::VOICE_ROOM_STATUS_CALLING)
            ->first();

        $voiceChatRoom->voiceChatRoomUser()->update([
            'ended_time' => now()
        ]);

        $voiceChatRoom->status = Consts::VOICE_ROOM_STATUS_ENDED;
        $voiceChatRoom->save();

        VoiceGroupUtils::clearShareVideo($roomId);

        event(new VoiceRoomClosed($voiceChatRoom->id));

        CalculateRoomCurrentSize::dispatch($roomId)->onQueue(Consts::QUEUE_VOICE_GROUP);
        CalculateRoomCategoryStatistic::dispatch($voiceChatRoom->game_id)->onQueue(Consts::QUEUE_VOICE_GROUP);

        $this->fireEventVoiceCategoryUpdated($voiceChatRoom->game_id);

        return $voiceChatRoom;
    }

    public function acceptQuestionV2($roomId, $questionId)
    {
        $acceptor = VoiceChatRoomUser::where('room_id', $roomId)
            ->where('user_id', Auth::id())
            ->whereIn('type', [Consts::ROOM_USER_TYPE_HOST, Consts::ROOM_USER_TYPE_MODERATOR])
            ->whereNull('ended_time')
            ->first();

        if (!$acceptor) {
            throw new VoiceGroupException('exceptions.not_permission_accept_question');
        }

        $answeringQuestion = RoomQuestion::where('room_id', $roomId)
            ->where('status', Consts::ROOM_QUESTION_STATUS_ANSWERING)
            ->exists();

        $question = RoomQuestion::where('id', $questionId)
            ->where('room_id', $roomId)
            ->first();
        $question->status = $answeringQuestion ? Consts::ROOM_QUESTION_STATUS_ACCEPTED : Consts::ROOM_QUESTION_STATUS_ANSWERING;
        $question->acceptor_id = $acceptor->user_id;
        $question->save();

        $question->user = $this->getUser($question->user_id);
        $question->acceptor = User::where('id', $question->acceptor_id)->value('username');

        if ($answeringQuestion) {
            event(new RoomQuestionAccepted($question));
        } else {
            event(new RoomQuestionAnswering($question));
        }
        CalculateAmaRoomQuestions::dispatch($roomId)->onQueue(Consts::QUEUE_VOICE_GROUP);
        return $question;
    }

    public function cancelQuestion($roomId, $questionId)
    {
        $member = VoiceChatRoomUser::where('room_id', $roomId)
            ->where('user_id', Auth::id())
            ->whereNull('ended_time')
            ->first();

        $question = RoomQuestion::where('id', $questionId)
            ->where('room_id', $roomId)
            ->where('user_id', Auth::id())
            ->first();

        if (!$member || !$question) {
            throw new VoiceGroupException('exceptions.not_permission_cancel_question');
        }

        $question->status = Consts::ROOM_QUESTION_STATUS_CANCELED;
        $question->save();
        $question->user = $this->getUser($question->user_id);

        event(new RoomQuestionCanceled($question));
        CalculateAmaRoomQuestions::dispatch($roomId)->onQueue(Consts::QUEUE_VOICE_GROUP);

        return $question;
    }

    public function answerQuestion($roomId, $questionId)
    {
        $acceptor = VoiceChatRoomUser::where('room_id', $roomId)
            ->where('user_id', Auth::id())
            ->whereIn('type', [Consts::ROOM_USER_TYPE_HOST, Consts::ROOM_USER_TYPE_MODERATOR])
            ->whereNull('ended_time')
            ->first();

        if (!$acceptor) {
            throw new VoiceGroupException('exceptions.not_permission_answer_question');
        }
        $currentAnsweringQuestion = $this->closeCurrentQuestion($roomId);

        $question = RoomQuestion::where('id', $questionId)
            ->where('room_id', $roomId)
            ->first();
        $question->status = Consts::ROOM_QUESTION_STATUS_ANSWERING;
        $question->save();

        $question->user = $this->getUser($question->user_id);
        event(new RoomQuestionAnswering($question));
        CalculateAmaRoomQuestions::dispatch($roomId)->onQueue(Consts::QUEUE_VOICE_GROUP);

        return $question;
    }

    public function getQueuedQuestions($roomId, $params)
    {
        return RoomQuestion::with(['user'])
            ->where('room_id', $roomId)
            ->whereIn('status', [Consts::ROOM_QUESTION_STATUS_ACCEPTED, Consts::ROOM_QUESTION_STATUS_ANSWERING])
            ->orderBy('created_at', 'asc')
            ->paginate(array_get($params, 'limit', Consts::DEFAULT_PER_PAGE));
    }

    public function getAskedQuestions($roomId, $params)
    {
        return RoomQuestion::with(['user'])
            ->where('room_id', $roomId)
            ->where('status', Consts::ROOM_QUESTION_STATUS_PENDING)
            ->orderBy('created_at', 'asc')
            ->paginate(array_get($params, 'limit', Consts::DEFAULT_PER_PAGE));
    }

    public function getQuestions($roomId, $params)
    {
        $statuses = [
            Consts::ROOM_QUESTION_STATUS_PENDING,
            Consts::ROOM_QUESTION_STATUS_ACCEPTED,
            Consts::ROOM_QUESTION_STATUS_ANSWERING
        ];

        $queryOrder = "CASE WHEN status = '" . Consts::ROOM_QUESTION_STATUS_ANSWERING ."' THEN 1 ";
        $queryOrder .= "WHEN status = '" . Consts::ROOM_QUESTION_STATUS_ACCEPTED ."' THEN 2 ";
        $queryOrder .= "ELSE 3 END";

        return RoomQuestion::with(['user'])
            ->where('room_id', $roomId)
            ->whereIn('status', $statuses)
            ->orderByRaw($queryOrder)
            ->get();
    }

    public function countQuestions($roomId)
    {
        $totalAsked = RoomQuestion::with(['user'])
            ->where('room_id', $roomId)
            ->where('status', Consts::ROOM_QUESTION_STATUS_PENDING)
            ->count('id');

        $totalQueued = RoomQuestion::with(['user'])
            ->where('room_id', $roomId)
            ->whereIn('status', [Consts::ROOM_QUESTION_STATUS_ACCEPTED, Consts::ROOM_QUESTION_STATUS_ANSWERING])
            ->count('id');

        return ['total_asked' => $totalAsked, 'total_queued' => $totalQueued];
    }

    // Voice Group
    public function createVoiceChatRoomV21($params)
    {
        $this->checkUserInOtherRoom();

        $user = User::withoutAppends()
            ->leftJoin('voice_group_managers', 'voice_group_managers.user_id', 'users.id')
            ->select('users.id', 'users.username', 'users.sex', 'users.avatar',
                DB::raw('(CASE WHEN voice_group_managers.deleted_at IS NULL THEN voice_group_managers.role ELSE NULL END) AS voice_group_role'))
            ->where('users.id', Auth::id())
            ->first();

        $currentMilis = time();
        $username = $user->username;
        $hash = gamelancer_hash("{$username}_{$currentMilis}");

        $params = array_filter($params);
        $isCommunityRoom = !!array_get($params, 'community_id');
        $type = $isCommunityRoom ? Consts::ROOM_TYPE_COMMUNITY : array_get($params, 'type', Consts::ROOM_TYPE_HANGOUT);
        $gameIdChatting = Game::where('type', Consts::CATEGORY_TYPE_CHAT)->first();
        $friendToInvite = array_get($params, 'friend_id', []);
        $gameId = $isCommunityRoom || $friendToInvite ? $gameIdChatting->id : array_get($params, 'game_id');
        $pinned = $user->voice_group_role === Consts::VOICE_GROUP_ROLE_ADMIN && !$isCommunityRoom
            ? Consts::TRUE : Consts::FALSE;

        $title = array_get($params, 'title') ? $params['title'] : VoiceGroupUtils::buildTitle($gameId, $type);

        $isPrivate = array_get($params, 'is_private');
        $room = VoiceChatRoom::create([
            'user_id'           => $user->id,
            'creator_id'        => $user->id,
            'pinned'            => $pinned,
            'game_id'           => $gameId,
            'is_private'        => $friendToInvite && !$isPrivate ? Consts::TRUE : array_get($params, 'is_private', Consts::FALSE),
            'name'              => $hash,
            'title'             => $title,
            'topic'             => array_get($params, 'topic'),
            'type'              => $type,
            'size'              => $isCommunityRoom ? Consts::COMMUNITY_VOICE_ROOM_SIZE : array_get($params, 'size', 100),
            'current_size'      => 1,
            'code'              => array_get($params, 'code'),
            'rules'             => array_get($params, 'rules'),
            'background_url'    => array_get($params, 'background_url'),
            'status'            => Consts::VOICE_ROOM_STATUS_CALLING,
            'community_id'      => array_get($params, 'community_id')
        ]);

        $voiceChatRoomUser = $this->createVoiceChatRoomUser(
            $room,
            [
                'user_id'           => $user->id
            ],
            [
                'type'              => Consts::ROOM_USER_TYPE_HOST,
                'sid'               => array_get($params, 'sid'),
                'username'          => array_get($params, 'username'),
                'started_time'      => now()
            ]
        );

        if ($room->type === Consts::ROOM_TYPE_AMA) {
            $roomSettings = RoomSetting::create([
                'room_id' => $room->id,
                'allow_ask_question' => Consts::TRUE
            ]);

            $room->allow_ask_question = $roomSettings->allow_ask_question;
        }

        $rtcToken = Agora::generateRtcToken($hash, $user->id);
        $rtmToken = Agora::generateRtmToken(strval($user->id));

        if ($friendToInvite) {
            foreach ($friendToInvite as $item) {
                $this->inviteUserIntoRoom(['room_id' => $room->id, 'user_id' => $item], true);
            }
        }

        $room->user = $user;
        event(new VoiceChatRoomCreated($room));

        CalculateRoomCategoryStatistic::dispatch($room->game_id)->onQueue(Consts::QUEUE_VOICE_GROUP);

        $this->fireEventVoiceCategoryUpdated($room->game_id);

        if (!$room->is_private) {
            $this->sendNotificationToFriends21($room, $friendToInvite);
        }

        return $room;
    }

    private function sendNotificationToFriends21($room, $exceptIds = [])
    {
        $communityService = new CommunityService();
        $communityMembers = $communityService->getListMember($room->community_id);
        $friends = $this->userService->getListFriend();
        $friends->each(function ($user, $key) use ($room, $exceptIds, $communityMembers) {
            $isInCommunity = $room->community_id ? in_array($user->id, $communityMembers) : true;
            if (!in_array($user->id, $exceptIds) && $isInCommunity) {
                $notificationParams = [
                    'user_id' => $user->id,
                    'type' => Consts::NOTIFY_TYPE_VOICE_ROOM_CREATED,
                    'message' => Consts::MESSAGE_NOTIFY_ROOM_CREATED,
                    'props' => [
                        'room_category' => $room->game_id,
                        'room_title' => $room->title
                    ],
                    'data' => [
                        'user'      => (object) ['id' => $room->user_id],
                        'room_name' => $room->name
                    ]
                ];
                $this->fireNotification(Consts::NOTIFY_TYPE_VOICE_ROOM, $notificationParams);
            }
        });
    }

    public function forceCreateRoomV21($params)
    {
        $curentRoom = $this->getCurrentRoom();
        if ($curentRoom->room_id) {
            $this->leaveVoiceChatRoom($curentRoom->room_id);
        }

        return $this->createVoiceChatRoomV21($params);
    }

    public function getUserTypeVoiceRoomCommunity($room, $receiverId) {
        $communityService = new CommunityService();
        $communityRole = $communityService->getRole($room->community_id, $receiverId);
        if ($communityRole === Consts::COMMUNITY_ROLE_OWNER) {
            return Consts::ROOM_USER_TYPE_HOST;
        }

        if ($communityRole === Consts::COMMUNITY_ROLE_LEADER) {
            return Consts::ROOM_USER_TYPE_MODERATOR;
        }

        return Consts::ROOM_USER_TYPE_SPEAKER;
    }
}
