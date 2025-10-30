<?php

namespace App\Http\Services;

use App\Consts;
use App\Events\CommunityInfoUpdated;
use App\Events\CommunityLeaderDowngraded;
use App\Events\CommunityLeaderUpgraded;
use App\Events\CommunityNameChangeRequestUpdated;
use App\Events\CommunityPostCreated;
use App\Events\CommunityPostDeleted;
use App\Events\CommunityPostPined;
use App\Events\CommunityPostReactionCreated;
use App\Events\CommunityPostReactionDeleted;
use App\Events\CommunityPostUnpinAllMessage;
use App\Events\CommunityRequestAccepted;
use App\Events\CommunityRequestRejected;
use App\Events\CommunityRequestCanceled;
use App\Events\CommunityPostUnpin;
use App\Events\CommunityUserExited;
use App\Events\CommunityUserInvited;
use App\Events\CommunityUserJoined;
use App\Events\CommunityUserKicked;
use App\Events\CommunityRequestCreated;
use App\Events\UserProfileUpdated;
use App\Events\UserUpdated;
use App\Exceptions\Reports\CommunityException;
use App\Exceptions\Reports\InvalidActionException;
use App\Exceptions\Reports\VoiceGroupException;
use App\Jobs\CalculateCommunityRoomStatistic;
use App\Jobs\CalculateCommunityStatistic;
use App\Jobs\CloseAllRoomCommunity;
use App\Jobs\CommunityAcceptAllRequest;
use App\Models\Community;
use App\Models\CommunityInvitation;
use App\Models\CommunityMember;
use App\Models\CommunityMessageReport;
use App\Models\CommunityNameChangeRequests;
use App\Models\CommunityReport;
use App\Models\CommunityRequest;
use App\Models\MattermostUser;
use App\Models\User;
use App\Models\VoiceChatRoom;
use App\Models\CommunityUserReport;
use App\Traits\NotificationTrait;
use App\Utils\CommunityUtils;
use App\Utils\UserOnlineUtils;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Mattermost;

class CommunityService
{
    use NotificationTrait;

    protected $fcmService;
    protected $chatService;
    private $userService;

    public function __construct()
    {
        $this->fcmService = new FirebaseService();
        $this->userService = new UserService;
    }

    public function getCommunities($params)
    {
        $searchKey = array_get($params, 'search_key');
        $userId = array_get($params, 'user_id');

        $data = Community::where('status', Consts::COMMUNITY_STATUS_ACTIVE)
            ->where('is_private', Consts::FALSE)
            ->when($searchKey, function ($q) use ($searchKey) {
                $q->where('name', 'like', '%' . $searchKey . '%');
            })
            ->when($userId, function ($q) use ($userId) {
                $queryOrder = "CASE WHEN creator_id = '" . $userId ."' THEN 1 ELSE 2 END";

                $q->whereHas('communityMember', function ($q2) use ($userId) {
                    $q2->where('user_id', $userId)
                        ->orderBy('created_at');
                })->orderByRaw($queryOrder);
            })
            ->orderBy('total_users', 'desc')
            ->paginate(array_get($params, 'limit', Consts::DEFAULT_PER_PAGE));

        //  handle permanently-delete-at
        $data->getCollection()->transform(function ($item) {
            if ($item->inactive_at) {
                $permanentlyDeleteAt = Carbon::createFromFormat('Y-m-d H:i:s', $item->inactive_at)->addDays(Consts::COMMUNITY_DAYS_FOR_GRACE_PERIOD)->format('Y-m-d H:i:s');
                $item->permanently_delete_at = $permanentlyDeleteAt;
            }
            return $item;
        });

        return $data;
    }

    public function getMyCommunities($params)
    {
        $searchKey = array_get($params, 'search_key');
        $userId = Auth::id();

        $data = Community::select('communities.*', 'community_members.role')
            ->join('community_members', 'communities.id', 'community_members.community_id')
            ->where('community_members.user_id', $userId)
            ->whereNull('community_members.deleted_at')
            ->when($searchKey, function ($q) use ($searchKey) {
                $q->where('communities.name', 'like', '%' . $searchKey . '%');
            })
            ->orderByRaw(
                "CASE WHEN communities.last_message_at is not null THEN communities.last_message_at ELSE communities.created_at END DESC"
            )
            ->paginate(array_get($params, 'limit', Consts::DEFAULT_PER_PAGE));

        //  handle permanently-delete-at
        $data->getCollection()->transform(function ($item) {
            if ($item->inactive_at) {
                $permanentlyDeleteAt = Carbon::createFromFormat('Y-m-d H:i:s', $item->inactive_at)->addDays(Consts::COMMUNITY_DAYS_FOR_GRACE_PERIOD)->format('Y-m-d H:i:s');
                $item->permanently_delete_at = $permanentlyDeleteAt;
            }
            return $item;
        });

        return $data;
    }

    public function updateCommunity($params)
    {
        $communityId = $params['id'];
        $isOwner = $this->checkOwnerPermission($communityId);

        if (!$isOwner) {
            throw new CommunityException('exceptions.community.not_permission_update_community');
        }

        $community = $this->checkCommunityActive($communityId);

        if (array_key_exists('photo', $params)) {
            $community->photo = array_get($params, 'photo');
        }

        if (array_key_exists('gallery_id', $params)) {
            $community->gallery_id = array_get($params, 'gallery_id');
        }

        if (array_key_exists('description', $params)) {
            $community->description = array_get($params, 'description');
        }

        if (array_key_exists('is_private', $params)) {
            $community->is_private = array_get($params, 'is_private');
        }

        if (array_key_exists('allow_share_screen', $params)) {
            $community->allow_share_screen = array_get($params, 'allow_share_screen');
        }

        $community->save();

        $this->fireEventCommunityRoomUpdated($communityId);

        if (array_key_exists('is_private', $params) &&  $params['is_private'] === Consts::FALSE) {
            CommunityAcceptAllRequest::dispatch($communityId)->onQueue(Consts::QUEUE_COMMUNITY);
        }

        return $community;
    }

    public function createCommunity($params)
    {
        $userId = Auth::id();
        $this->getMattermostUserId($userId);
        $slug = CommunityUtils::buildChannelSlug($params['name']);
        // handle mattermost
        $mattermostChannel = Mattermost::createChannel([
            'name' => $slug,
            'display_name' => $params['name'],
            'type' => Consts::MATTERMOST_CHANNEL_TYPE_OPEN
        ]);

        $community = Community::create([
            'mattermost_channel_id' => $mattermostChannel->id,
            'name' => $params['name'],
            'slug' => $slug,
            'description' => array_get($params, 'description'),
            'total_users' => 1,
            'leader_count' => 1,
            'member_count' => 0,
            'photo' => array_get($params, 'photo'),
            'gallery_id' => array_get($params, 'gallery_id'),
            'is_private' => $params['is_private'],
            'creator_id' => $userId
        ]);

        $communityMember = CommunityMember::create(['community_id' => $community->id, 'user_id' => $userId, 'role' => Consts::COMMUNITY_ROLE_OWNER]);

        // Update community in cache.
        CommunityUtils::addNewChannel($mattermostChannel);
        CommunityUtils::addNewMember($mattermostChannel->id, $communityMember);
        $community = $community->fresh();
        $community->community_member_count = 1;
        $community->role = Consts::COMMUNITY_ROLE_OWNER;

        //TODO handle Performance, comment out
//        $this->performFirehosePut($community, 'Create group community');

        // if (!$community->is_private) {
        //     $this->sendNotificationToFriends($community);
        // }

        event(new UserUpdated($userId));

        return $community;
    }

    public function removeCommunity($communityId)
    {
        $hasPermission = $this->checkOwnerPermission($communityId);
        if (!$hasPermission) {
            throw new CommunityException('exceptions.community.not_permission_remove_community');
        }

        $destroy = Community::where('id', $communityId)->update(
            [
                'status' => Consts::COMMUNITY_STATUS_DELETED,
                'inactive_at' => Carbon::now()
            ]
        );

        $this->fireEventCommunityRoomUpdated($communityId);
        CloseAllRoomCommunity::dispatch($communityId)->onQueue(Consts::QUEUE_COMMUNITY);
        CalculateCommunityRoomStatistic::dispatch($communityId)->onQueue(Consts::QUEUE_COMMUNITY);
        return $destroy;
    }

    public function deactivateCommunity($communityId) {
        $hasPermission = $this->checkOwnerPermission($communityId);

        if (!$hasPermission) {
            throw new CommunityException('exceptions.community.not_permission');
        }

        $baseQuery = Community::where('id', $communityId);
        $baseQuery->update(['status' => Consts::COMMUNITY_STATUS_DEACTIVATED]);

        $dataChannel = $baseQuery->first();

        $this->fireEventCommunityRoomUpdated($communityId);
        CloseAllRoomCommunity::dispatch($communityId)->onQueue(Consts::QUEUE_COMMUNITY);
        CalculateCommunityRoomStatistic::dispatch($communityId)->onQueue(Consts::QUEUE_COMMUNITY);
        return $dataChannel;
    }

    public function reactivateCommunity($communityId) {
        $hasPermission = $this->checkOwnerPermission($communityId);

        if (!$hasPermission) {
            throw new CommunityException('exceptions.community.not_permission');
        }

        $baseQuery = Community::where('id', $communityId);
        $baseQuery->update(['status' => Consts::COMMUNITY_STATUS_ACTIVE, 'inactive_at' => null]);

        $dataChannel = $baseQuery->first();
        $this->fireEventCommunityRoomUpdated($communityId);

        return $dataChannel;
    }

    public function makeLeader($userId, $communityId)
    {
        $communityMember = CommunityMember::where('community_id', $communityId)
            ->where('user_id', $userId)
            ->where('role', '!=', Consts::COMMUNITY_ROLE_OWNER)
            ->first();

        if (!$communityMember) {
            throw new CommunityException('exceptions.community.user_not_in_community');
        }

        $hasPermission = $this->checkOwnerPermission($communityId);
        if (!$hasPermission) {
            throw new CommunityException('exceptions.community.not_permission_make_leader');
        }

        $communityMember->role = Consts::COMMUNITY_ROLE_LEADER;
        $communityMember->save();

        $communityMember->user = $this->getUser($communityMember->user_id);

//        $this->sendPromotedNotification(Community::find($communityId), $userId);
        event(new CommunityLeaderUpgraded($communityMember));
        CalculateCommunityStatistic::dispatch($communityId)->onQueue(Consts::QUEUE_COMMUNITY);
        return $communityMember;
    }

    public function removeLeader($userId, $communityId)
    {
        $communityMember = CommunityMember::where('community_id', $communityId)
            ->where('user_id', $userId)
            ->where('role', Consts::COMMUNITY_ROLE_LEADER)
            ->first();

        if (!$communityMember) {
            throw new CommunityException('exceptions.community.user_not_in_community');
        }

        $hasPermission = $this->checkOwnerPermission($communityId);
        if (!$hasPermission) {
            throw new CommunityException('exceptions.community.not_permission_remove_leader');
        }

        $communityMember->role = Consts::COMMUNITY_ROLE_MEMBER;
        $communityMember->save();

        $communityMember->user = $this->getUser($communityMember->user_id);
//        $this->sendDemotedNotification(Community::find($communityId), $userId);
        CalculateCommunityStatistic::dispatch($communityId)->onQueue(Consts::QUEUE_COMMUNITY);
        event(new CommunityLeaderDowngraded($communityMember));
        return $communityMember;
    }

    private function performFirehosePut($channel, $when, $type = null)
    {
        $data = [
            'channel_id' => $channel->channel_id,
            'from_user_id' => Auth::id()
        ];

        if (!empty($type)) {
            $data = array_merge($data, ['type' => $type]);
        }

        Aws::performFirehosePut([
            'when' => $when,
            'data' => $data
        ]);
    }

    public function reportCommunity($communityId, $params)
    {
        $community = $this->checkCommunityActive($communityId);

        $reporterId = Auth::id();
        $reporter = CommunityMember::where('community_id', $communityId)
            ->where('user_id', $reporterId)
            ->exists();
        if (!$reporter) {
            throw new CommunityException('exceptions.community.not_in_community');
        }

        if ($this->checkReportExisted($communityId)) {
            throw new CommunityException('exceptions.community.already_report_community');
        }

        return CommunityReport::create([
            'community_id' => $communityId,
            'reporter_id' => $reporterId,
            'reason_id' => array_get($params, 'reason_id'),
            'details' => array_get($params, 'details'),
            'status' => Consts::REPORT_STATUS_PROCESSING
        ]);
    }

    public function getRequests($communityId, $params)
    {
        $this->checkOwnerOrLeaderPermission($communityId);

        return CommunityRequest::whereHas('user', function ($q) {
                $q->where('status', Consts::USER_ACTIVE);
            })
            ->with(['user'])->where('community_id', $communityId)
            ->where('status', Consts::COMMUNITY_STATUS_CREATED)
            ->paginate(array_get($params, 'limit', Consts::DEFAULT_PER_PAGE));
    }

    public function exitCommunity($communityId, $userId = null)
    {
        $hasOwner = $this->checkOwnerPermission($communityId);
        if ($hasOwner) {
            throw new CommunityException('exceptions.community.owner_not_have_permission_leave');
        }

        $userId = $userId ?: Auth::id();

        $communityMember = CommunityMember::where('community_id', $communityId)
            ->where('user_id', $userId)
            ->first();

        $community = Community::where('id', $communityId)
            ->where('status', Consts::COMMUNITY_STATUS_ACTIVE)
            ->first();

        if (!$communityMember || !$community) {
            throw new CommunityException('exceptions.community.member_or_community_not_exist');
        }

        $communityMember->deleted_at = now();
        $communityMember->save();

        $communityMember->user = $this->getUser($communityMember->user_id);

        // delete community invitation
        CommunityInvitation::where('community_id', $communityId)->where('receiver_id', $userId)->forceDelete();

        $this->userLeaveVoiceChatRoom($communityId, $userId);

        event(new CommunityUserExited($communityMember));
        event(new UserUpdated($userId));
        event(new UserProfileUpdated($userId));
        CalculateCommunityStatistic::dispatch($communityId)->onQueue(Consts::QUEUE_COMMUNITY);

        return $communityMember;
    }

    public function acceptRequestToJoin($communityId, $userId)
    {
        $this->checkOwnerOrLeaderPermission($communityId);

        $communityRequest = CommunityRequest::where('community_id', $communityId)
            ->where('user_id', $userId)
            ->where('status', Consts::COMMUNITY_STATUS_CREATED)
            ->first();

        if (!$communityRequest) {
            throw new CommunityException('exceptions.community.request_to_join_not_exist');
        }

        // check user kicked
        // $userKicked = CommunityMember::where('community_id', $communityId)
        //     ->where('user_id', $userId)
        //     ->whereNotNull('kicked_by')
        //     ->exists();

        // if ($userKicked) {
        //     throw new CommunityException('exceptions.community.user_have_kicked_from_community');
        // }

        $alreadyMember = CommunityMember::where('community_id', $communityId)
            ->where('user_id', $userId)
            ->exists();

        if ($alreadyMember) {
            throw new CommunityException('exceptions.community.already_member_in_community');
        }

        $communityRequest->status = Consts::COMMUNITY_STATUS_ACCEPTED;
        $communityRequest->save();

        $community = $this->checkCommunityActive($communityId);

        $this->createCommunityMember($community, null, $userId);
        event(new CommunityRequestAccepted($communityRequest));
        CalculateCommunityStatistic::dispatch($communityId)->onQueue(Consts::QUEUE_COMMUNITY);
        // $this->sendAcceptNotification($community, $userId);

        return true;
    }

    public function rejectRequestToJoin($communityId, $userId)
    {
        $this->checkOwnerOrLeaderPermission($communityId);

        $communityRequest = CommunityRequest::where('community_id', $communityId)
            ->where('user_id', $userId)
            ->where('status', Consts::COMMUNITY_STATUS_CREATED)
            ->first();

        if (!$communityRequest) {
            throw new CommunityException('exceptions.community.request_to_join_not_exist');
        }

        $alreadyMember = CommunityMember::where('community_id', $communityId)
            ->where('user_id', $userId)
            ->exists();

        if ($alreadyMember) {
            throw new CommunityException('exceptions.community.already_member_in_community');
        }

        $communityRequest->status = Consts::COMMUNITY_STATUS_REJECT;
        $communityRequest->save();

        $community = Community::find($communityId);
        // $this->sendRejectNotification($community, $userId);

        event(new CommunityRequestRejected($communityRequest));
        CalculateCommunityStatistic::dispatch($communityId)->onQueue(Consts::QUEUE_COMMUNITY);
        return true;

    }

    public function cancelRequestToJoin($communityId)
    {
        $communityRequest = CommunityRequest::where('community_id', $communityId)
            ->where('user_id', Auth::id())
            ->where('status', Consts::COMMUNITY_STATUS_CREATED)
            ->first();

        if (!$communityRequest) {
            throw new CommunityException('exceptions.community.request_to_join_not_exist');
        }

        $communityRequest->status = Consts::COMMUNITY_STATUS_CANCELED;
        $communityRequest->save();

        event(new CommunityRequestCanceled($communityRequest));
        CalculateCommunityStatistic::dispatch($communityId)->onQueue(Consts::QUEUE_COMMUNITY);
        return true;
    }

    public function kickUser($communityId, $userId)
    {
        $communityMember = CommunityMember::where('community_id', $communityId)
            ->where('user_id', $userId)
            ->where('role', '!=', Consts::COMMUNITY_ROLE_OWNER)
            ->first();

        if (!$communityMember) {
            throw new CommunityException('exceptions.community.user_not_in_community');
        }

        $eliminatorId = Auth::id();
        $leader = CommunityMember::where('community_id', $communityId)
            ->where('user_id', $eliminatorId)
            ->when(
                $communityMember->role === Consts::COMMUNITY_ROLE_LEADER,
                function ($query) {
                    $query->where('role', Consts::COMMUNITY_ROLE_OWNER);
                },
                function ($query) {
                    $query->whereIn('role', [Consts::COMMUNITY_ROLE_OWNER, Consts::COMMUNITY_ROLE_LEADER]);
                }
            )
            ->exists();
        if (!$leader) {
            throw new CommunityException('exceptions.community.not_permission_kick_user');
        }

        // handle mattermost
//        try {
//            $mattermostChannelId = $this->getMattermostChannelId($communityId);
//            $mattermostUserId = $this->getMattermostUserId($userId);
//            Mattermost::removeUserFromChannel($mattermostChannelId, $mattermostUserId);
//        } catch (\Exception $e) {
//            logger()->error('==================MATTERMOST_REMOVE_USER_CHANNEL_ERROR', [
//                $e->getMessage()
//            ]);
//        }

        // TODO handle cache if needed
        $communityMember->deleted_at = Carbon::now();
        $communityMember->kicked_by = $eliminatorId;
        $communityMember->save();

        CommunityInvitation::where('community_id', $communityId)
            ->where('receiver_id', $userId)
            ->forceDelete();

        $communityMember->user = $this->getUser($communityMember->user_id);
        event(new CommunityUserKicked($communityMember));
        event(new UserUpdated($userId));
        event(new UserProfileUpdated($userId));
        CalculateCommunityStatistic::dispatch($communityId)->onQueue(Consts::QUEUE_COMMUNITY);

        $community = Community::find($communityId);
//        $this->sendKickedNotification($community, $userId);
        $this->userLeaveVoiceChatRoom($communityId, $userId);

        return true;
    }

    public function reactionPost($communityId, $postId)
    {
        $userId = Auth::id();
        $mattermostUserId = $this->getMattermostUserId($userId);
        $emojiName = Consts::MATTERMOST_EMOJI_NAME;
        // handle mattermost
        Mattermost::reactionPost($mattermostUserId, $postId, $emojiName);
        $customData = collect(['user_id' => $mattermostUserId, 'post_id' => $postId, 'community_id' => $communityId]);
        event(new CommunityPostReactionCreated($customData));
        return true;
    }

    public function deleteReactionPost($communityId, $postId)
    {
        $userId = Auth::id();
        $mattermostUserId = $this->getMattermostUserId($userId);
        $emojiName = Consts::MATTERMOST_EMOJI_NAME;
        // handle mattermost
        Mattermost::deleteReaction($mattermostUserId, $postId, $emojiName);
        $customData = collect(['user_id' => $mattermostUserId, 'post_id' => $postId, 'community_id' => $communityId]);
        event(new CommunityPostReactionDeleted($customData));
        return true;
    }

    public function getReactionPost($postId)
    {
        return Mattermost::getReactions($postId);
    }

    public function getInviteList($communityId, $params)
    {
        // except user already in community
        $exceptUsers = CommunityMember::where('community_id', $communityId)->pluck('user_id')->toArray();

        $friends = $this->userService->getListFriend($exceptUsers, $params);
        $invitationUsers = CommunityInvitation::where('community_id', $communityId)
            ->where('sender_id', Auth::id())
            ->where('status', Consts::COMMUNITY_STATUS_CREATED)
            ->whereIn('receiver_id', $friends->pluck('id'))
            ->pluck('receiver_id');

        $friends->transform(function ($item) use ($invitationUsers) {
            $item->invited = in_array($item->id, $invitationUsers->toArray());
            return $item;
        });
        return $friends;
    }

    public function inviteUser($params)
    {
        $communityId = array_get($params, 'community_id');
        $receiverId = array_get($params, 'user_id');

        // check user request
        $alreadyRequest = CommunityRequest::where('community_id', $communityId)
            ->where('user_id', $receiverId)
            ->where('status', Consts::COMMUNITY_STATUS_CREATED)
            ->first();

        if ($alreadyRequest) {
            throw new CommunityException('exceptions.community.already_request_in_community');
        }

        $sender = Auth::user();

        $userBlockList = $this->userService->getUserBlocklists($sender->id);
        if (in_array($receiverId, $userBlockList->toArray())) {
            throw new CommunityException('exceptions.community.already_user_in_blocklist');
        }

        $alreadyMember = CommunityMember::where('community_id', $communityId)
            ->where('user_id', $receiverId)
            ->exists();

        if ($alreadyMember) {
            throw new CommunityException('exceptions.community.already_member_in_community');
        }

        $community = $this->checkCommunityActive($communityId);

        $alreadyInvited = CommunityInvitation::where('community_id', $communityId)
            ->where('receiver_id', $receiverId)
            ->where('sender_id', $sender->id)
            ->withTrashed()
            ->exists();

        if ($alreadyInvited) {
            throw new CommunityException('exceptions.community.already_invited_to_community');
        }

        $invitation = CommunityInvitation::create([
            'community_id' => $communityId,
            'receiver_id' => $receiverId,
            'sender_id' => $sender->id,
            'status' => Consts::COMMUNITY_STATUS_CREATED
        ]);

        $invitation->community = $community;
        $invitation->sender_username = $sender->username;
        $invitation->user_info = $this->getUserInfo($sender->id);
        event(new CommunityUserInvited($receiverId, $invitation));

//        $this->sendInviteNotification($community, $invitation);

        return true;
    }

    public function joinCommunity($communityId)
    {
        $community = $this->checkCommunityAvailable($communityId);
        $userId = Auth::id();
        $alreadyMember = CommunityMember::where('community_id', $communityId)
            ->where('user_id', $userId)
            ->first();

        if ($alreadyMember) {
            throw new CommunityException('exceptions.community.already_member_in_community');
        }

        $userBlockList = $this->userService->getUserBlocklists($community->creator_id);
        if (in_array($userId, $userBlockList->toArray())) {
            throw new CommunityException('exceptions.community.user_already_in_blocklist_of_owner');
        }

        if ($community->is_private) {
            return $this->askToJoin($community);
        }

        return $this->joinPublic($community);
    }

    public function askToJoin($community)
    {
        $sender = Auth::user();
        $userId = $sender->id;
        $communityId = $community->id;
        // has kicked
        $userKicked = CommunityMember::where('community_id', $communityId)
            ->where('user_id', $userId)
            ->whereNotNull('kicked_by')->exists();

        if ($userKicked) {
            throw new CommunityException('exceptions.community.user_have_kicked_from_community');
        }

        $requestToJoin = CommunityRequest::create(['community_id' => $communityId, 'user_id' => $userId, 'status' => Consts::COMMUNITY_STATUS_CREATED]);

        $requestToJoin->community = $community;
        $requestToJoin->sender_username = $sender->username;
        $requestToJoin->user = $this->getUser($userId);

        $communityMember = CommunityMember::where('community_id', $communityId)
            ->where('role', Consts::COMMUNITY_ROLE_OWNER)
            ->first();

        $ownerId = $communityMember->user_id;
        event(new CommunityRequestCreated($requestToJoin));
        CalculateCommunityStatistic::dispatch($communityId)->onQueue(Consts::QUEUE_COMMUNITY);
//        $this->sendRequestNotification($community, $requestToJoin, $ownerId);

        return true;
    }

    public function joinPublic($community)
    {
        $this->createCommunityMember($community);
        CalculateCommunityStatistic::dispatch($community->id)->onQueue(Consts::QUEUE_COMMUNITY);
        return true;
    }

    public function checkCommunityAvailable($param)
    {
        $userId = Auth::id();
        $isOwner = $this->checkOwnerPermission($param, $userId);
        return $this->getCommunityInfo($param, $isOwner);
    }

    private function getPostsForMattermostChannel($mattermostChannelId, $input)
    {
        $pagination = [
            'per_page' => array_get($input, 'limit', 20)
        ];

        return Mattermost::getPostsForChannel($mattermostChannelId, $pagination);
    }

    public function getCommunityMembers($param, $params = [])
    {
        $searchKey = array_get($params, 'search_key');
        $channel = Community::where(function ($query) use ($param) {
            $query->where('id', $param)
                ->orWhere('slug', $param);
        })->first();

        if (!$channel) {
            return [];
        }

        $communityId = $channel->id;

        $users = CommunityMember::whereHas('user', function ($q) {
                $q->where('status', Consts::USER_ACTIVE);
            })
            ->with(['user'])
            ->where('community_id', $communityId)
            ->when(array_get($params, 'role'), function ($query) use ($params) {
                $query->where('role', array_get($params, 'role'));
            })
            ->when($searchKey, function ($q) use ($searchKey) {
                $q->whereHas('user', function ($query2) use ($searchKey) {
                    $query2->where('username', 'like', "%{$searchKey}%");
                });
            });


        if (array_get($params, 'limit')) {
            return $users->paginate(array_get($params, 'limit'));
        }

        $dataMember = $users->with(['user'])->get();
        return $dataMember;
    }

    public function reportPost($communityId, $params)
    {
        $this->checkCommunityActive($communityId);

        $reporterId = Auth::id();
        $reporter = CommunityMember::where('community_id', $communityId)
            ->where('user_id', $reporterId)
            ->exists();

        if (!$reporter) {
            throw new CommunityException('exceptions.community.not_in_community');
        }

        if ($this->checkReportPostExisted($communityId, $params['post_id'])) {
            throw new CommunityException('exceptions.community.already_report_community');
        }

        return CommunityMessageReport::create([
            'community_id' => $communityId,
            'user_id' => $params['user_id'],
            'mattermost_post_id' => $params['post_id'],
            'reporter_id' => $reporterId,
            'reason_id' => array_get($params, 'reason_id'),
            'details' => array_get($params, 'details'),
            'status' => Consts::REPORT_STATUS_PROCESSING
        ]);
    }

    public function getPost($postId)
    {
        $result = Mattermost::getPost($postId);
        if (!$result) {
            return \stdClass::class;
        }

        $channelMembers = CommunityUtils::getChannelMembers($result->channel_id);
        $user = $channelMembers->firstWhere('chat_user_id', $result->user_id);
        if (!empty($user)) {
            $result->props->user = $user;
        } else {
            $result->props->user = $this->updateCacheMember($result->user_id, $result->channel_id);
        }

        if ($result->parent_id) {
            $post = Mattermost::getPost($result->parent_id);
            $userRootPost = $channelMembers->firstWhere('chat_user_id', $post->user_id);
            if (!empty($userRootPost)) {
                $post->props->user = $userRootPost;
            } else {
                $post->props->user = $this->updateCacheMember($post->user_id, $post->channel_id);
            }
            $result->root_post = $post;
        }

        return $result;
    }

    public function getPostsForCommunityById($mattermostChannelId, $input)
    {
        $inputCommon = [
            'per_page' => array_get($input, 'limit', 30),
        ];

        if (!empty($input['page'])) {
            $inputCommon['page'] = array_get($input, 'page');
        }

        if (empty($input['next_post_id']) && empty($input['prev_post_id'])) {
            return $this->getDataPost($mattermostChannelId, $inputCommon);
        }

        $order = [];
        $posts = collect();
        if (!empty($input['next_post_id'])) { // get after posts
            $inputAfter = array_merge(['after' => array_get($input, 'next_post_id')], $inputCommon);
            $afterData = $this->getDataPost($mattermostChannelId, $inputAfter);
            $order = array_merge($order, $afterData->order);
            $posts = collect($afterData->posts);

            if (!empty($input['prev_post_id'])) {
                $currentPost = $this->getPost($input['prev_post_id']);
                $order = array_merge($order, [$currentPost->id]);
                $posts = $posts->merge([$currentPost->id => $currentPost]);
            }
        }


        if (!empty($input['prev_post_id'])) { // get before posts
            $inputBefore = array_merge(['before' => array_get($input, 'prev_post_id')], $inputCommon);
            $beforeData = $this->getDataPost($mattermostChannelId, $inputBefore);
            $order = array_merge($order, $beforeData->order);

            $posts = $posts->merge($beforeData->posts);
        }
        $orderPost = $this->handleOrderPost($order);
        $next = $orderPost['next_post_id'];
        $previous = $orderPost['prev_post_id'];

        return ['order' => $order, 'posts' => $posts, 'prev_post_id' => $previous, 'next_post_id' => $next];
    }

    public function getDataPost($mattermostChannelId, $params) {
        $result = Mattermost::getPostsForChannel($mattermostChannelId, $params);

        $posts = collect($result->posts);

        if ($posts->isEmpty()) {
            return $result;
        }
        $channelMembers = CommunityUtils::getChannelMembers($mattermostChannelId);
        $result->posts = $posts->reject(function ($item) {
            return !empty($item->type);
        })->map(function ($item) use ($channelMembers) {
            $user = $channelMembers->firstWhere('chat_user_id', $item->user_id);
            if (!empty($user)) {
                $item->props->user = $user;
            } else {
                $item->props->user = $this->updateCacheMember($item->user_id, $item->channel_id);
            }

            if ($item->parent_id) {
                $post = Mattermost::getPost($item->parent_id);
                $userRootPost = $channelMembers->firstWhere('chat_user_id', $post->user_id);
                if (!empty($userRootPost)) {
                    $post->props->user = $userRootPost;
                } else {
                    $post->props->user = $this->updateCacheMember($post->user_id, $post->channel_id);
                }
                $item->root_post = $post;
            }

            return $item;
        });
        $arrIdSystemMsg = $posts->where('type', '!=', null)->pluck('id')->toArray();
        $postsOrder = array_diff($result->order, $arrIdSystemMsg);
        $result->order = array_values($postsOrder);
        $orderPost = $this->handleOrderPost($postsOrder);
        $result->prev_post_id = $orderPost['prev_post_id'];
        $result->next_post_id = $orderPost['next_post_id'];
        return $result;
    }

    private function handleOrderPost($order) {
        $prevPostId = !empty($order) ? end($order) : null;
        $nextPostId = !empty($order) ? reset($order) : null;
        return ['prev_post_id' => $prevPostId, 'next_post_id' => $nextPostId];
    }

    public function createPost($posts)
    {
        $posts = $this->buildPostsData($posts);
        $user = Auth::user();
        $post = Mattermost::createPost($user->mattermostUser->mattermost_user_id, $posts);

        $community = Community::where('mattermost_channel_id', $posts['channel_id'])->first();
        $community->last_message_at = Carbon::now();
        $community->save();

        // Update last_post for channel in cache
        CommunityUtils::updateChannelWhenNewPost($post);

        event(new CommunityPostCreated($community->id, $post));
        return $post;
    }

    private function getGroupByMattermostId($mattermostChannelId)
    {
        return Community::where('mattermost_channel_id', $mattermostChannelId)
            ->first();
    }

    private function buildPostsData($posts)
    {
        if (array_get($posts, 'root_id')) {
            $rootId = array_get($posts, 'root_id');
            $parentId = array_get($posts, 'root_id');
            $infoPost = $this->getPost($rootId);

            if ($infoPost->root_id) {
                $rootId = $infoPost->root_id; // If replying to a message, this message is a reply to a previous message
            }

            $posts['root_id'] = $rootId;
            $posts['parent_id'] = $parentId;
        }

        $userId = array_get($posts, 'user_id', Auth::id());
        $posts['props']['temp_id'] = array_get($posts, 'temp_id');
        $posts['props']['user_id'] = $this->getMattermostUserId($userId);
        $posts['props']['user'] = $this->getUser($userId);
        if (!empty($posts['images'])) {
            $props = [
                'images' => [$posts['images']]
            ];

            $posts['props'] = array_merge($posts['props'], $props);
        }

        return $posts;
    }

    public function pinPost($communityId, $postId)
    {
        $this->checkOwnerOrLeaderPermission($communityId);
        $user = Auth::user();
        $post = Mattermost::getPost($postId);
        if (isset($post->props->status) && $post->props->status === Consts::COMMUNITY_STATUS_DELETED) {
            throw new CommunityException('exceptions.community.pin_message_removed');
        }

        $channelMembers = CommunityUtils::getChannelMembers($post->channel_id);
        $userCache = $channelMembers->firstWhere('chat_user_id', $post->user_id);
        if (!empty($userCache)) {
            $post->props->user = $userCache;
        } else {
            $post->props->user = $this->updateCacheMember($post->user_id, $post->channel_id);
        }

        Mattermost::pinPost($user->mattermostUser->mattermost_user_id, $postId);

        event(new CommunityPostPined($communityId, $post));
        return $post;
    }

    public function unpinPost($communityId, $postId)
    {
        $this->checkOwnerOrLeaderPermission($communityId);
        $user = Auth::user();
        Mattermost::unpinPost($user->mattermostUser->mattermost_user_id, $postId);

        event(new CommunityPostUnpin($communityId, $postId));
        return true;
    }

    public function getPinnedPosts($communityId, $mattermostChannelId)
    {
//        $community = Community::find('id', $communityId)->withTrashed()->first();

        $user = Auth::user();
        $result = Mattermost::getChannelsPinnedPosts($user->mattermostUser->mattermost_user_id, $mattermostChannelId);
        $posts = collect($result->posts);
        $channelMembers = CommunityUtils::getChannelMembers($mattermostChannelId);
        $result->posts = $posts->map(function ($item) use ($channelMembers) {
            $user = $channelMembers->firstWhere('chat_user_id', $item->user_id);
            if (!empty($user)) {
                $item->props->user = $user;
            } else {
                $user = MattermostUser::with(['user'])->where('mattermost_user_id', $item->user_id)->first();
                if (!empty($user)) {
                    // update to cache
                    CommunityUtils::updateChannelMembers($item->channel_id, $user->user);
                    $item->props->user = $user;
                }
            }
            return $item;
        });

        return $result;
    }

    public function unpinAllPosts($communityId, $mattermostChannelId)
    {
        $this->checkOwnerOrLeaderPermission($communityId);
        $user = Auth::user();
        $mattermostUserId = $user->mattermostUser->mattermost_user_id;
        $result = Mattermost::getChannelsPinnedPosts($mattermostUserId, $mattermostChannelId);

        foreach ($result->order as $item) {
            Mattermost::unpinPost($mattermostUserId, $item);
        }

        event(new CommunityPostUnpinAllMessage($communityId));
        return true;
    }

    public function deletePost($params)
    {
        $communityId = $params['community_id'];
        $postId = $params['post_id'];
        $posts['is_pinned'] = false;
        $posts['props']['deleted_by'] = $this->getDeletedBy($postId, $communityId);
        $posts['props']['status'] = Consts::COMMUNITY_STATUS_DELETED;
        $response = Mattermost::patchPost($postId, $posts);

        event(new CommunityPostDeleted($communityId, $response));
        return $response;
    }

    public function getCommunityDetail($slug)
    {
        $detail = Community::where('slug', $slug)->first();
        if ($detail->inactive_at) {
            $detail->permanently_delete_at = Carbon::createFromFormat('Y-m-d H:i:s', $detail->inactive_at)->addDays(Consts::COMMUNITY_DAYS_FOR_GRACE_PERIOD)->format('Y-m-d H:i:s');
        }

        if (!$detail) {
            throw new CommunityException('exceptions.community.community_not_existed');
        }

        $leader = CommunityMember::whereHas('user', function ($q) {
                $q->where('status', Consts::USER_ACTIVE);
            })
            ->with(['user'])
            ->where('community_id', $detail->id)
            ->whereIn('role', [Consts::COMMUNITY_ROLE_OWNER, Consts::COMMUNITY_ROLE_LEADER])
            ->get();
        $custom = collect(['list_leader_role' => $leader]);
        return $custom->merge($detail);
    }

    public function checkCommunityExisted($slug)
    {
        $community = Community::where('slug', $slug)->first();
        if (!$community) {
            throw new CommunityException('exceptions.community.community_not_existed');
        }

        return $community;
    }

    public function getNameChangeRequest($communityId)
    {
        return CommunityNameChangeRequests::where('community_id', $communityId)
            ->where('status', Consts::COMMUNITY_STATUS_PENDING)
            ->first();
    }

    public function nameChangeRequest($params)
    {
        $userId = Auth::id();
        $communityId = $params['community_id'];
        $community = $this->checkCommunityActive($communityId);

        $isOwner = $this->checkOwnerPermission($communityId);

        if (!$isOwner) {
            throw new CommunityException('exceptions.community.not_permission_update_community');
        }

        $checkRequestProcessing = CommunityNameChangeRequests::where('community_id', $communityId)
            ->where('status', Consts::COMMUNITY_STATUS_PENDING)
            ->exists();

        if ($checkRequestProcessing) {
            throw new CommunityException('exceptions.community.already_name_change_request');
        }

        $data = CommunityNameChangeRequests::create([
            'community_id' => $communityId,
            'request_user_id' => $userId,
            'reason_id' => $params['reason_id'],
            'old_name' => $community->name,
            'new_name' => $params['new_name'],
            'status' => Consts::COMMUNITY_STATUS_PENDING
        ]);
        $data = $data->refresh();
        event(new CommunityNameChangeRequestUpdated($data));

        return $data;
    }

    public function cancelNameChangeRequest($id)
    {
        $checkData = CommunityNameChangeRequests::where('id', $id)
            ->where('status', Consts::COMMUNITY_STATUS_PENDING)
            ->first();

        if (!$checkData) {
            throw new InvalidActionException();
        }

        $checkData->update(['status' => Consts::COMMUNITY_STATUS_CANCELED]);
        $data = $checkData->fresh();
        event(new CommunityNameChangeRequestUpdated($data));

        return $data;
    }

    public function checkUserJoinRequest($id)
    {
        $status = CommunityRequest::where('community_id', $id)
            ->where('user_id', Auth::id())
            ->where('status', Consts::COMMUNITY_STATUS_CREATED)->exists();

        return ['has_requested' => $status];
    }

    private function getCommunityInfo($param, $isOwner = false) {
        $groupInfo = Community::where(function ($query) use ($param) {
            $query->where('id', $param)
                ->orWhere('slug', $param);
        })->first();

        if (!$groupInfo) {
            throw new CommunityException('exceptions.community.community_not_existed');
        }

        return $groupInfo;
    }

    public function acceptInvite($communityId)
    {
        $community = $this->checkCommunityActive($communityId);

        $userId = Auth::id();

        $communityInvite = CommunityInvitation::where('community_id', $communityId)
            ->where('receiver_id', $userId)
            ->where('status', Consts::COMMUNITY_STATUS_CREATED)
            ->first();

        if (!$communityInvite) {
            throw new CommunityException('exceptions.community.invite_not_exist');
        }

        $alreadyMember = $this->checkMemberExists($communityId, $userId);
        if ($alreadyMember) {
            throw new CommunityException('exceptions.community.already_member_in_community');
        }

        $userBlockList = $this->userService->getUserBlocklists($community->creator_id);
        if (in_array($userId, $userBlockList->toArray())) {
            throw new CommunityException('exceptions.community.user_already_in_blocklist_of_owner');
        }

        $communityInvite->status = Consts::COMMUNITY_STATUS_ACCEPTED;
        $communityInvite->save();

        $dataMember = $this->createCommunityMember($community, $communityInvite, null);
        $this->fireEventCommunityRoomUpdated($communityId);
        CalculateCommunityStatistic::dispatch($community->id)->onQueue(Consts::QUEUE_COMMUNITY);
        return $dataMember;
    }

    public function getMyRole($communityId)
    {
        $userId = Auth::id();
        $checkOwner = $this->checkOwnerPermission($communityId, $userId);
        $community = $this->getCommunityInfo($communityId, $checkOwner);

        if (!$community->first()) {
            throw new CommunityException('exceptions.community.community_not_existed');
        }

        return CommunityMember::where('community_id', $communityId)
            ->where('user_id', $userId)
            ->first();
    }

    private function createCommunityMember($community, $invitation = null, $userId = null)
    {
        $userId = $userId ?: Auth::id();

        // check user request
        $userRequest = CommunityRequest::where('community_id', $community->id)
            ->where('user_id', $userId)
            ->where('status', Consts::COMMUNITY_STATUS_CREATED)
            ->first();

        if ($userRequest) {
            $userRequest->status = Consts::COMMUNITY_STATUS_CANCELED;
            $userRequest->save();
        }

        $dataMember = CommunityMember::create(['community_id' => $community->id, 'user_id' => $userId, 'role' => Consts::COMMUNITY_ROLE_MEMBER]);
        // add new member to cache
        CommunityUtils::addNewMember($community->mattermost_channel_id, $dataMember);

        $dataMember->user = $this->getUser($userId);
        $dataMember->invited_user_id = $invitation ? $invitation->sender_id : null;

        // handle mattermost
        $mattermostUserId = $this->getMattermostUserId($userId);
        Mattermost::addUserToChannel($community->mattermost_channel_id, $mattermostUserId);

        event(new CommunityUserJoined($dataMember));
        event(new UserUpdated($userId));
        event(new UserProfileUpdated($userId));
        return true;
    }

    public function checkRandomRoom($communityId, $params)
    {
        $getRooms = VoiceChatRoom::where('community_id', $communityId)
            ->where('voice_chat_rooms.status', Consts::VOICE_ROOM_STATUS_CALLING)
            ->get()
            ->pluck('id');

        if (count($getRooms) < 1) {
            throw new VoiceGroupException('exceptions.no_available_room');
        }

        $randomId = $getRooms->random();

        return VoiceChatRoom::find($randomId);
    }

    private function sendInviteNotification($community, $invitation)
    {
        $notificationParams = [
            'user_id' => $invitation->receiver_id,
            'type' => Consts::NOTIFY_TYPE_COMMUNITY_INVITATION,
            'message' => Consts::MESSAGE_NOTIFY_COMMUNITY_INVITATION,
            'props' => [
                'community_name' => $community->name
            ],
            'data' => [
                'user' => (object) ['id' => $invitation->sender_id],
                'community_name' => $community->name,
                'invitation_id' => $invitation->id,
                'slug' => $community->slug,
                'community_id' => $community->id
            ]
        ];
        $this->fireNotification(Consts::NOTIFY_TYPE_COMMUNITY, $notificationParams);
    }

    private function sendNotificationToFriends($community)
    {
        $friends = $this->userService->getListFriend();
        $friends->each(function ($user, $key) use ($community) {
            $notificationParams = [
                'user_id' => $user->id,
                'type' => Consts::NOTIFY_TYPE_COMMUNITY_CREATED,
                'message' => Consts::MESSAGE_NOTIFY_COMMUNITY_CREATED,
                'props' => [
                    'community_name' => $community->name
                ],
                'data' => [
                    'user' => (object) ['id' => $community->creator_id],
                    'community_name' => $community->name,
                    'slug' => $community->slug
                ]
            ];
            $this->fireNotification(Consts::NOTIFY_TYPE_COMMUNITY, $notificationParams);
        });
    }

    private function sendRequestNotification($community, $requestToJoin, $ownerId)
    {
        $notificationParams = [
            'user_id' => $ownerId,
            'type' => Consts::NOTIFY_TYPE_COMMUNITY_REQUEST_TO_JOIN,
            'message' => Consts::MESSAGE_NOTIFY_COMMUNITY_REQUEST_TO_JOIN,
            'props' => [
                'community_name' => $community->name
            ],
            'data' => [
                'user' => (object) ['id' => $requestToJoin->user_id],
                'community_id' => $community->id,
                'community_name'     => $community->name,
                'community_request_id' => $requestToJoin->id
            ]
        ];

        $this->fireNotification(Consts::NOTIFY_TYPE_COMMUNITY, $notificationParams);
    }

    private function sendAcceptNotification($community, $receiverId)
    {
        $notificationParams = [
            'user_id' => $receiverId,
            'type' => Consts::NOTIFY_TYPE_COMMUNITY_ACCEPT_TO_JOIN,
            'message' => Consts::MESSAGE_NOTIFY_COMMUNITY_ACCEPT_TO_JOIN,
            'props' => [
                'community_name' => $community->name
            ],
            'data' => [
                'user' => (object) ['id' => $receiverId],
                'community_id' => $community->id,
                'community_name' => $community->name,
                'slug' => $community->slug,
            ]
        ];

        $this->fireNotification(Consts::NOTIFY_TYPE_COMMUNITY, $notificationParams);
    }

    private function sendRejectNotification($community, $receiverId)
    {
        $notificationParams = [
            'user_id' => $receiverId,
            'type' => Consts::NOTIFY_TYPE_COMMUNITY_REJECT_TO_JOIN,
            'message' => Consts::MESSAGE_NOTIFY_COMMUNITY_REJECT_TO_JOIN,
            'props' => [
                'community_name' => $community->name
            ],
            'data' => [
                'community_id' => $community->id,
                'community_name' => $community->name,
            ]
        ];
        $this->fireNotification(Consts::NOTIFY_TYPE_COMMUNITY, $notificationParams);
    }

    private function sendKickedNotification($community, $receiverId)
    {
        $notificationParams = [
            'user_id' => $receiverId,
            'type' => Consts::NOTIFY_TYPE_COMMUNITY_REMOTE,
            'message' => Consts::MESSAGE_NOTIFY_COMMUNITY_KICKED,
            'props' => [
                'community_name' => $community->name
            ],
            'data' => [
                'community_id' => $community->id,
                'community_name' => $community->name,
            ]
        ];
        $this->fireNotification(Consts::NOTIFY_TYPE_COMMUNITY, $notificationParams);
    }

    private function sendPromotedNotification($community, $receiverId)
    {
        $notificationParams = [
            'user_id' => $receiverId,
            'type' => Consts::NOTIFY_TYPE_COMMUNITY_REMOTE,
            'message' => Consts::MESSAGE_NOTIFY_COMMUNITY_PROMOTED,
            'props' => [],
            'data' => [
                'community_id' => $community->id,
                'community_photo' => $community->photo
            ]
        ];
        $this->fireNotification(Consts::NOTIFY_TYPE_COMMUNITY, $notificationParams);
    }

    private function sendDemotedNotification($community, $receiverId)
    {
        $notificationParams = [
            'user_id' => $receiverId,
            'type' => Consts::NOTIFY_TYPE_COMMUNITY_REMOTE,
            'message' => Consts::MESSAGE_NOTIFY_COMMUNITY_DEMOTED,
            'props' => [],
            'data' => [
                'community_id' => $community->id,
                'community_photo' => $community->photo
            ]
        ];
        $this->fireNotification(Consts::NOTIFY_TYPE_COMMUNITY, $notificationParams);
    }

    private function getMattermostUserId($userId)
    {
        $mattermostUser = User::find($userId)->mattermostUser;
        if (empty($mattermostUser)) {
            throw new CommunityException('exceptions.community.mattermost_user_not_found');
        }

        return $mattermostUser->mattermost_user_id;
    }

    private function getMattermostChannelId($communityId) {
        return Community::where('id', $communityId)->withTrashed()->first()->mattermost_channel_id;
    }

    private function checkOwnerPermission($param, $userId = null)
    {
        $userId = $userId ?: Auth::id();
        $channelInfo = $groupInfo = Community::where(function ($query) use ($param) {
            $query->where('id', $param)
                ->orWhere('slug', $param);
        })->first();

        if (!$channelInfo) {
            throw new CommunityException('exceptions.community.community_not_existed');
        }

        $communityId = $channelInfo->id;
        return CommunityMember::where('user_id', $userId)
            ->where('community_id', $communityId)
            ->where('role', Consts::COMMUNITY_ROLE_OWNER)
            ->exists();
    }

    private function checkOwnerOrLeaderPermission($param)
    {
        $channelInfo = $this->getCommunityInfo($param);
        $communityId = $channelInfo->id;
        $userId = Auth::id();
        $checkPermission = CommunityMember::where('user_id', $userId)
            ->where('community_id', $communityId)
            ->where(function ($q) {
                $q->where('role', Consts::COMMUNITY_ROLE_OWNER)->orWhere('role', Consts::COMMUNITY_ROLE_LEADER);
            })
            ->first();

        if (!$checkPermission) {
            throw new CommunityException('exceptions.community.not_permission');
        }

        return $checkPermission;
    }

    public function getRole($param, $userId = null)
    {
        $userId = $userId ?: Auth::id();
        $communityInfo = Community::where(function ($query) use ($param) {
            $query->where('id', $param)
                ->orWhere('slug', $param);
        })->first();

        if (!$communityInfo) {
            return null;
        }

        $communityId = $communityInfo->id;
        $role = CommunityMember::where('user_id', $userId)
            ->where('community_id', $communityId)
            ->first();

        if (empty($role)) {
            return null;
        }

        return $role->role;
    }

    private function getUser($userId)
    {
        $user = DB::table('users')->join('user_settings', 'user_settings.id', 'users.id')
            ->where('users.id', $userId)
            ->select('users.id', 'users.avatar', 'users.sex', 'users.username', 'users.user_type', 'users.is_vip', 'user_settings.online as online_setting')
            ->first();
        return (object) $user;
    }

    public function checkReportExisted($communityId)
    {
        return CommunityReport::where('community_id', $communityId)
            ->where('reporter_id', Auth::id())
            ->where('status', Consts::REPORT_STATUS_PROCESSING)
            ->exists();
    }

    public function checkReportPostExisted($communityId, $postId)
    {
        return CommunityMessageReport::where('community_id', $communityId)
            ->where('mattermost_post_id', $postId)
            ->where('reporter_id', Auth::id())
            ->where('status', Consts::REPORT_STATUS_PROCESSING)
            ->exists();
    }

    private function fireEventCommunityRoomUpdated($communityId)
    {
        $community = Community::where('id', $communityId)->first();
        if ($community->inactive_at) {
            $permanentlyDeleteAt = Carbon::createFromFormat('Y-m-d H:i:s', $community->inactive_at)->addDays(Consts::COMMUNITY_DAYS_FOR_GRACE_PERIOD)->format('Y-m-d H:i:s');
            $community->permanently_delete_at = $permanentlyDeleteAt;
        }
        event(new CommunityInfoUpdated($community));
    }

    public function checkMemberExists($communityId, $userId, $includeDeleted = false) {
        if ($includeDeleted) {
            return CommunityMember::where('community_id', $communityId)
                ->where('user_id', $userId)
                ->withTrashed()
                ->exists();
        }
        return CommunityMember::where('community_id', $communityId)
            ->where('user_id', $userId)
            ->exists();
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
            $this->throwUserInvalid();
        }

        return (object) [
            'id' => $user->id,
            'username' => $user->username,
            'email' => $user->email,
            'sex' => $user->sex,
            'avatar' => $user->avatar,
            'user_type' => $user->user_type,
            'ranking_id' => $user->ranking_id,
            'setting' => [
                'online' => $user->online
            ]
        ];
    }

    private function throwUserInvalid ()
    {
        throw new \Exception("Something wrong! The user is invalid.");
    }

    public function reportUser($communityId, $params)
    {
        $this->checkCommunityActive($communityId);

        $reporterId = Auth::id();
        $reporter = CommunityMember::where('community_id', $communityId)
            ->where('user_id', $reporterId)
            ->exists();
        if (!$reporter) {
            throw new VoiceGroupException('exceptions.not_in_room');
        }

        $reportedId = array_get($params, 'reported_user');
        $reportedUser = CommunityMember::where('community_id', $communityId)
            ->where('user_id', $reportedId)
            ->exists();
        if (!$reportedUser) {
            throw new VoiceGroupException('exceptions.user_not_in_room');
        }

        if ($this->checkUserReportExisted($communityId, $reportedId)) {
            throw new VoiceGroupException('exceptions.already_report_user');
        }

        $report = CommunityUserReport::create([
            'community_id'      => $communityId,
            'reporter_id'       => $reporterId,
            'reported_user_id'  => $reportedId,
            'reason_id'         => array_get($params, 'reason_id'),
            'details'           => array_get($params, 'details'),
            'status'            => Consts::REPORT_STATUS_PROCESSING
        ]);

        return true;
    }

    public function checkUserReportExisted($communityId, $reportedUser)
    {
        return CommunityUserReport::where('community_id', $communityId)
            ->where('reporter_id', Auth::id())
            ->where('reported_user_id', $reportedUser)
            ->where('status', Consts::REPORT_STATUS_PROCESSING)
            ->exists();
    }

    public function checkCommunityReportExisted($communityId)
    {
        return CommunityReport::where('community_id', $communityId)
            ->where('reporter_id', Auth::id())
            ->where('status', Consts::REPORT_STATUS_PROCESSING)
            ->exists();
    }

    private function userLeaveVoiceChatRoom($communityId, $userId)
    {
        $voiceRoom = DB::table('voice_chat_room_users')
            ->join('voice_chat_rooms', 'voice_chat_rooms.id', 'voice_chat_room_users.room_id')
            ->where('voice_chat_room_users.user_id', $userId)
            ->where('voice_chat_rooms.community_id', $communityId)
            ->where('voice_chat_rooms.status', Consts::VOICE_STATUS_CALLING)
            ->whereNull('voice_chat_room_users.ended_time')
            ->first();

        if ($voiceRoom) {
            $voiceService = new VoiceService();
            $voiceService->leaveVoiceChatRoom($voiceRoom->room_id, $userId);
        }
    }

    public function handleJobAcceptRequest($communityId, $userId)
    {
        $communityRequest = CommunityRequest::where('community_id', $communityId)
            ->where('user_id', $userId)
            ->where('status', Consts::COMMUNITY_STATUS_CREATED)
            ->first();
        $communityRequest->status = Consts::COMMUNITY_STATUS_ACCEPTED;
        $communityRequest->save();

        $community = Community::find($communityId);

        $this->createCommunityMember($community, null, $userId);
        event(new CommunityRequestAccepted($communityRequest));
        CalculateCommunityStatistic::dispatch($communityId)->onQueue(Consts::QUEUE_COMMUNITY);
        // $this->sendAcceptNotification($community, $userId);

        return true;
    }

    private function checkCommunityActive($communityId)
    {
        $community = Community::where('id', $communityId)->where('status', Consts::COMMUNITY_STATUS_ACTIVE)->first();
        if (!$community) {
            throw new CommunityException('exceptions.community.community_not_existed_or_deactivated');
        }

        return $community;
    }

    private function updateCacheMember($mattermostUserId, $channelId)
    {
        $user = MattermostUser::with(['user'])->where('mattermost_user_id', $mattermostUserId)->first();
        if (!empty($user)) {
            // update to cache
            CommunityUtils::updateChannelMembers($channelId, $user->user);
            return $user->user;
        }
        return new \stdClass();
    }

    private function getDeletedBy($postId, $communityId)
    {
        $userId = Auth::id();
        $post = $this->getPost($postId);
        if ($post->props->user['id'] == $userId) {
            return Consts::COMMUNITY_ROLE_MEMBER;
        }

        $role = $this->checkOwnerOrLeaderPermission($communityId);
        return $role->role;
    }

    public function getCommunityMembersOnline($param, $params = [])
    {
        $searchKey = array_get($params, 'search_key');
        $channel = Community::where(function ($query) use ($param) {
            $query->where('id', $param)
                ->orWhere('slug', $param);
        })->first();

        if (!$channel) {
            return [];
        }

        $communityId = $channel->id;
        $arrUserIdOnline = UserOnlineUtils::getUserIdOnlines();
        $users = CommunityMember::where('community_id', $communityId)
            ->whereHas('user', function ($q) use ($arrUserIdOnline) {
                $q->whereIn('id', $arrUserIdOnline);
            })
            ->with(['user'])
            ->when(array_get($params, 'role'), function ($query) use ($params) {
                $query->where('role', array_get($params, 'role'));
            })
            ->when($searchKey, function ($q) use ($searchKey) {
                $q->whereHas('user', function ($query2) use ($searchKey) {
                    $query2->where('username', 'like', "%{$searchKey}%");
                });
            });


        return $users->paginate(array_get($params, 'limit', Consts::DEFAULT_PER_PAGE));
    }

    public function leaveAnyCommunity($userId)
    {
        $communitiesMember = CommunityMember::where('user_id', $userId)->where('role', '!=', Consts::COMMUNITY_ROLE_OWNER)->get();
        if ($communitiesMember->count() > 0) {
            foreach ($communitiesMember as $item) {
                CommunityMember::where('id', $item->id)->delete();
                $communityId = $item->community_id;
                CalculateCommunityStatistic::dispatch($communityId)->onQueue(Consts::QUEUE_COMMUNITY);
            }
        }

        $communitiesRequest = CommunityRequest::where('user_id', $userId)->where('status', Consts::COMMUNITY_STATUS_CREATED)->get();
        if ($communitiesRequest->count() > 0) {
            foreach ($communitiesRequest as $item) {
                CommunityRequest::where('id', $item->id)->update(['status' => Consts::COMMUNITY_STATUS_CANCELED]);
                $communityId = $item->community_id;
                CalculateCommunityStatistic::dispatch($communityId)->onQueue(Consts::QUEUE_COMMUNITY);
            }
        }
    }

    public function getListMember($communityId) {
        return CommunityMember::where('community_id', $communityId)->pluck('user_id')->toArray();
    }
}
