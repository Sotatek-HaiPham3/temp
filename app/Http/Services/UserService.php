<?php

namespace App\Http\Services;

use App\Consts;

use App\Events\UserAddFriendUpdated;
use App\Events\UserBlocked;
use App\Events\UserUnblocked;
use App\Events\UserFollowingUpdated;
use App\Events\UserUnfollowUpdated;
use App\Events\UserUnfriendUpdated;
use App\Exceptions\Reports\PhoneNumberNotSupportedException;
use App\Mails\ResetPasswordCodeMail;
use App\Models\Community;
use App\Models\CommunityMember;
use App\Models\SocialUser;
use App\Models\UserBlockList;
use App\Models\UserDeviceRegister;
use App\Models\UserBalance;
use DateTime;
use App\Models\UserSocialNetwork;
use App\Models\User;
use Carbon\Carbon;
use DeviceDetector\DeviceDetector;
use DeviceDetector\Parser\Device\DeviceParserAbstract;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Http\Services\GameProfileService;
use App\Http\Services\AdminService;
use App\Http\Services\SessionService;
use App\Utils\BigNumber;
use Exception;
use App\Utils\OtpUtils;
use App\Utils;
use App\PhoneUtils;
use App\Utils\BalanceUtils;
use App\Utils\ChatUtils;
use App\Utils\CommunityUtils;
use App\Events\BalanceUpdated;
use App\Events\UserUpdated;
use App\Events\UserProfileUpdated;
use App\Events\UserSettingsUpdated;
use App\Events\EmailChanged;
use App\Events\UsernameChanged;
use App\Events\PhoneNumberChanged;
use App\Events\TaskCollected;
use App\Events\DailyCheckinCollected;
use App\Models\Game;
use App\Models\Language;
use App\Models\GamelancerInfo;
use App\Models\GamelancerAvailableTime;
use App\Models\UserPhoto;
use App\Models\InvitationCode;
use App\Models\SessionReview;
use App\Models\UserFollowing;
use App\Models\ChangeEmailHistory;
use App\Models\UserSetting;
use App\Models\Setting;
use App\Models\Session;
use App\Models\UserReport;
use App\Models\UserInterestsGame;
use App\Models\ChangeUsernameHistory;
use App\Models\ChangePhoneNumberHistory;
use App\Models\ExperiencePoint;
use App\Models\DailyCheckin;
use App\Models\Tasking;
use App\Models\TaskingReward;
use App\Models\CollectingTasking;
use App\Models\CollectingTaskingReward;
use App\Models\UserRanking;
use App\Models\VoiceChatRoomUser;
use App\Jobs\CalculateUserFollow;
use App\Jobs\GenerateInvitationCodeJob;
use App\Jobs\AddKlaviyoMailList;
use App\Jobs\CollectTaskingJob;
use App\Jobs\SendSmsNotificationJob;
use App\Mails\VerificationChangeMailQueue;
use App\Mails\VerificationChangeUsernameQueue;
use App\Mails\VerificationChangePhoneNumberQueue;
use App\Mails\VerificationMailQueue;
use App\Mails\OtpMailQueue;
use App\Mails\AuthorizationMailQueue;
use App\Mails\ChangePasswordMail;
use App\Exceptions\Reports\ChangeEmailException;
use App\Exceptions\Reports\ChangeUsernameException;
use App\Exceptions\Reports\ChangePhoneNumberException;
use App\Exceptions\Reports\InvalidRequestException;
use App\Exceptions\Reports\InvalidActionException;
use App\Exceptions\Reports\ChangeConcurrentlyEmailOrPhoneOrUsernameException;
use App\Exceptions\Reports\InvalidBalanceException;
use App\Exceptions\Reports\AccountNotActivedException;
use App\Exceptions\Reports\SecurityException;
use App\Exceptions\Reports\InvalidCodeException;
use App\Exceptions\Reports\InvalidDataException;
use Validator;
use Mattermost;
use SystemNotification;
use App\Traits\NotificationTrait;
use Mail;
use App\Utils\TimeUtils;
use App\Utils\RankingUtils;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use App\Jobs\SendSystemNotification;
use Socialite;

class UserService extends BaseService
{
    use NotificationTrait;

    private $gameProfileService;
    private $adminService;

    public function __construct()
    {
        $this->gameProfileService = new GameProfileService;
        $this->adminService = new AdminService;
    }

    const CACHE_LIVE_TIME = 5; // minutes

    public function getCurrentDevice($name, $userId = null)
    {
        DeviceParserAbstract::setVersionTruncation(DeviceParserAbstract::VERSION_TRUNCATION_NONE);
        $deviceDetector = new DeviceDetector($_SERVER['HTTP_USER_AGENT']);
        $deviceDetector->parse();

        $device = new UserDeviceRegister;
        $device->user_id = $userId ?? Auth::id();
        $device->kind = $deviceDetector->getDeviceName();
        $device->name = $name;

        $clientInfo = $deviceDetector->getClient();
        if (!empty($clientInfo['name']) && !empty($clientInfo['version'])) {
            $device->platform = $clientInfo['name'] . " " . $clientInfo['version'];
        }

        $systemInfo = $deviceDetector->getOs();
        if (!empty($systemInfo['name']) && !empty($systemInfo['version'])) {
            $device->operating_system = $systemInfo['name'] . " " . $systemInfo['version'];
        }

        $payload = [$device->user_id, $device->kind, $device->platform, $device->operating_system];
        $device->user_device_identify = base64url_encode(implode('_', $payload));

        $existedDevice = UserDeviceRegister::where('user_device_identify', $device->user_device_identify)->first();

        if ($existedDevice) {
            return $existedDevice;
        }

        $device->save();

        return $device;
    }

    public function addMoreBalance($userId, $amount, $currency = Consts::CURRENCY_COIN)
    {
        $this->updateBalance($userId, Consts::TRUE, $amount, $currency);
    }

    public function subtractBalance($userId, $amount, $currency = Consts::CURRENCY_COIN)
    {
        $this->updateBalance($userId, Consts::FALSE, $amount, $currency);
    }

    private function updateBalance($userId, $isAddition, $amount, $currency = Consts::CURRENCY_COIN)
    {
        $balance = $this->getUserBalanceAndLock($userId);
        $newBalance = BigNumber::new($balance->{$currency})->sub($amount)->toString();
        if ($isAddition) {
            $newBalance = BigNumber::new($balance->{$currency})->add($amount)->toString();
        }
        if (BigNumber::new($newBalance)->comp(0) < 0) {
            throw new Exception(__('user.balance_negative'));
        }

        $balance->{$currency} = $newBalance;
        $balance->save();

        event(new BalanceUpdated($userId, $balance));

        return $balance;
    }

    public function getUserBalanceAndLock($userId, $currency = 'usd')
    {
        $balance = UserBalance::where('id', $userId)
            ->lockForUpdate()
            ->first();
        if (empty($balance)) {
            throw new InvalidBalanceException('exceptions.not_existed_balance');
        }
        return $balance;
    }

    public function getUserBalances($userId)
    {
        $balance = UserBalance::where('id', $userId)->first();

        if (!$balance) {
            return ['coin' => 0, 'bar' => 0];
        }

        return BalanceUtils::standardValue($userId, $balance);
    }

    public function createNewUserBalance($userId)
    {
        return UserBalance::insert([
            'id' => $userId,
            'coin' => 0,
            'bar' => 0,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now()
        ]);
    }

    public function getUserProfile($userId, $loadRelation = true)
    {
        $user = User::withoutAppends()->with([
                'availableTimes', 'socialNetworks', 'photos', 'idols', 'fans', 'settings', 'statistic', 'personality',
                'visibleSettings', 'emailChanging', 'phoneChanging', 'phoneChangeCode'
            ])
            ->leftJoin('user_rankings', 'user_rankings.user_id', 'users.id')
            ->leftJoin('voice_group_managers', 'voice_group_managers.user_id', 'users.id')
            ->where('users.id', $userId)
            ->select('users.id', 'users.email', 'users.username', 'users.email_verified', 'users.phone_number',
                'users.phone_verified', 'users.phone_country_code', 'users.level', 'users.avatar', 'users.description',
                'users.languages', 'users.last_time_active', 'users.user_type', 'users.is_vip', 'users.sex', 'users.dob',
                'users.phone_verify_code', 'user_rankings.ranking_id', 'user_rankings.total_exp', 'user_rankings.intro_step', 'users.deleted_at', 'users.status',
                DB::raw('(CASE WHEN voice_group_managers.deleted_at IS NULL THEN voice_group_managers.role ELSE NULL END) AS voice_group_role'),
                DB::raw('(CASE WHEN users.password IS NULL THEN 0 ELSE 1 END) AS password_filled')
            )
            ->first();

        $user->chat_user = [
            'user_id' => $user->mattermostUser->user_id,
            'chat_user_id' => $user->mattermostUser->mattermost_user_id
        ];
        unset($user->mattermostUser);

        $user->is_social_user = !empty($user->socialUser);

        $personality = [];
        $user->personality->groupBy('review_tag_id')
            ->each(function ($item) use (&$personality) {
                $res = $item->first();
                $res->quantity = $item->sum('quantity');
                unset($res->review_type);
                $personality[] = $res;
            });

        $user->existed_verify_code = !empty($user->phone_verify_code);
        unset($user->phone_verify_code);

        $user->existed_verify_code_change = !empty($user->phoneChangeCode->verification_code);
        unset($user->phoneChangeCode);

        $user->tagStatistic = $personality;
        unset($user->personality);

        $user->blocked_users = $this->getUserBlocklists($user->id);

        $user->communities = CommunityMember::where('user_id', $user->id)->pluck('community_id');

        $user->communities_available = $this->getCommunityAvailable($user->id);

        $user->email = Utils::removeUserAutoEmail($user->email);

        return $user;
    }

    public function getVisibleSettings($userId) {
        $user = UserSetting::select('id', 'visible_age', 'visible_gender', 'visible_following', 'online', 'cover');
        return $user;
    }

    public function createSocialNetwork($params)
    {
        $link = $this->saveUserSocialNetwork($params);

        $userId = Auth::id();
        event(new UserUpdated($userId));
        event(new UserProfileUpdated($userId));

        return $link;
    }

    public function updateSocialNetwork($params)
    {
        $listNetworks = array_keys(Consts::SOCIAL_NETWORKS_LINK);
        $userId = Auth::id();

        foreach ($params as $social) {
            $this->saveUserSocialNetwork($social);
            if (($key = array_search($social['social_type'], $listNetworks)) !== false) {
                unset($listNetworks[$key]);
            }
        }

        UserSocialNetwork::where('user_id', $userId)
            ->whereIn('type', $listNetworks)
            ->delete();

        event(new UserUpdated($userId));
        event(new UserProfileUpdated($userId));

        return [];
    }

    private function saveUserSocialNetwork($params)
    {
        $type = array_get($params, 'social_type');
        $socialId = array_get($params, 'social_id');

        $fullUrl = $this->buildSocialUrl($type, $socialId);
        $userId = Auth::id();

        $link = UserSocialNetwork::firstOrNew([
            'type' => $type,
            'user_id' => $userId
        ]);

        $link->url = $fullUrl;
        $link->social_id = $socialId;
        $link->save();

        return $link;
    }

    private function buildSocialUrl($socicalType, $socialId)
    {
        $configSocials = Consts::SOCIAL_NETWORKS_LINK;

        $socialList = array_keys($configSocials);

        if (in_array($socicalType, $socialList)) {
            return sprintf($configSocials[$socicalType], $socialId);
        }

        return null;
    }

    public function createGamelancerInfo($data)
    {
        $autoApproved = Consts::FALSE;

        $code = array_get($data, 'invitation_code', null);
        $isApproveVip = Setting::where('key', Consts::VIP_SETTING)
            ->where('value', Consts::TRUE)
            ->exists();

        if ($isApproveVip && $code) {
            $existsInvitationCode = InvitationCode::where('code', $code);
            if ($existsInvitationCode) {
                $autoApproved = Consts::TRUE;
            }
        }

        $this->createAvailableTimes($data['available_times'], $data['timeoffset']);
        $gameProfile = $this->gameProfileService->createGameProfileFromBecomeGamelancer($data['session'], $autoApproved);

        $gamelancerInfo = GamelancerInfo::firstOrNew(['user_id' => Auth::id()]);

        $gamelancerInfo->total_hours        = $data['total_hours'];
        $gamelancerInfo->social_link_id     = $this->createSocialNetwork($data)->id;
        $gamelancerInfo->status             = $autoApproved ? Consts::GAMELANCER_INFO_STATUS_APPROVED : Consts::GAMELANCER_INFO_STATUS_PENDING;
        $gamelancerInfo->introduction       = $data['introduction'];
        $gamelancerInfo->invitation_code    = $code;
        $gamelancerInfo->game_profile_id    = $gameProfile->id;
        $gamelancerInfo->save();

        $user = Auth::user();

        if ($autoApproved) {
            // submit via vip link
            $user->user_type = Consts::USER_TYPE_PREMIUM_GAMELANCER;
            $user->description = $data['introduction'];
            $user->save();

            InvitationCode::where('code', $code)->delete();
            event(new UserUpdated(Auth::id()));
            AddKlaviyoMailList::dispatch($user, Consts::KALVIYO_ACTION_UPDATE);
        } else {
            // approved to free gamelancer
            $this->adminService->approveFreeGamelancer($gamelancerInfo->id);
        }

        return $gamelancerInfo;
    }

    public function getInvitationCodeForVip($isProcess = Consts::FALSE)
    {
        $isApproveVip = Setting::where('key', Consts::VIP_SETTING)
            ->where('value', Consts::TRUE)
            ->exists();

        if (!$isApproveVip) {
            return null;
        }

        $res = InvitationCode::whereNull('taken_at')->first();
        if ($res) {
            $res->taken_at = now();
            $res->save();
            return $res->code;
        }

        if (!$isProcess) {
            GenerateInvitationCodeJob::dispatchNow();
            return $this->getInvitationCodeForVip(Consts::TRUE);
        }

        return null;
    }

    public function getUserInfoByUsername($username)
    {
        $user = User::with(['socialNetworks', 'photos', 'availableTimes', 'visibleSettings', 'statistic', 'personality',
            'idols', 'fans', 'userRanking'])
            ->leftJoin('user_rankings', 'user_rankings.user_id', 'users.id')
            ->select('users.id', 'users.email', 'users.username', 'users.level', 'users.dob', 'users.sex', 'users.avatar', 'users.audio', 'users.description', 'users.languages',
                'users.last_time_active', 'users.user_type', 'users.is_vip', 'user_rankings.ranking_id', 'users.status', 'users.deleted_at')
            ->where('users.username', $username)
            ->whereIn('users.status', [Consts::USER_ACTIVE, Consts::USER_DELETED])
            ->first();

        if (!$user) {
            return null;
        }

        $personality = [];
        $user->personality->groupBy('review_tag_id')
            ->each(function ($item) use (&$personality) {
                $res = $item->first();
                $res->quantity = $item->sum('quantity');
                unset($res->review_type);
                $personality[] = $res;
            });

        $user->tagStatistic = $personality;
        unset($user->personality);

        $user->email = Utils::concealEmail($user->email);

        $user->blocked_users = $this->getUserBlocklists($user->id);

        $user->communities = CommunityMember::where('user_id', $user->id)->pluck('community_id');

        $user->communities_available = $this->getCommunityAvailable($user->id);

        $user->email = Utils::removeUserAutoEmail($user->email);

        return $user;
    }

    public function getAvailableTimes($userId, $timeoffset)
    {
        $times = GamelancerAvailableTime::where('user_id', $userId)->get();

        return TimeUtils::convertTimeRangesUtcToClient($times, $timeoffset);
    }

    public function createAvailableTimes($availableTimes, $timeoffset)
    {
        GamelancerAvailableTime::addNewTime($availableTimes, $timeoffset);

        $userId = Auth::id();

        event(new UserUpdated($userId));
        event(new UserProfileUpdated($userId));
    }

    public function addAvailableTime($params)
    {
        $time = GamelancerAvailableTime::addNewTime([$params], $params['timeoffset']);

        $userId = Auth::id();

        event(new UserUpdated($userId));
        event(new UserProfileUpdated($userId));

        return $time;
    }

    public function deleteAvailableTime($params)
    {
        $userId = Auth::id();
        $delete = GamelancerAvailableTime::deleteAvailableTime($params);

        event(new UserUpdated($userId));
        event(new UserProfileUpdated($userId));

        return $delete;
    }

    public function getUserPhotos($params)
    {
        return UserPhoto::where('user_id', $params['user_id'])
            ->when(!empty($params['type']), function ($query) use ($params) {
                $query->where('type', $params['type']);
            })
            ->paginate(array_get($params, 'limit', Consts::DEFAULT_PER_PAGE));
    }

    public function createUserPhoto($data)
    {
        $userId = Auth::id();
        $data = array_merge($data, ['user_id' => $userId]);
        $photo = UserPhoto::create($data);

        event(new UserUpdated($userId));
        event(new UserProfileUpdated($userId));

        return $photo;
    }

    public function deleteUserPhoto($id)
    {
        $userId = Auth::id();
        $deletePhoto = UserPhoto::where('id', $id)
            ->where('user_id', $userId)
            ->delete();

        event(new UserUpdated($userId));
        event(new UserProfileUpdated($userId));

        return $deletePhoto;
    }

    public function getUserReviews($userId, $params)
    {
        $userReviewSession = SessionReview::with(['userReview', 'tags'])
            ->join('sessions', 'sessions.id', 'session_reviews.object_id')
            ->join('game_profiles', 'game_profiles.id', 'sessions.game_profile_id')
            ->join('games', 'games.id', 'game_profiles.game_id')
            ->select('session_reviews.id', 'session_reviews.reviewer_id', 'games.title as game_title' ,'session_reviews.rate', 'session_reviews.object_type', 'session_reviews.description', 'session_reviews.recommend', 'session_reviews.created_at')
            ->where('object_type', Consts::OBJECT_TYPE_SESSION)
            ->where('session_reviews.user_id', $userId);

        $userReviewBounty = SessionReview::with(['userReview', 'tags'])
            ->join('bounties', 'bounties.id', 'session_reviews.object_id')
            ->join('games', 'games.id', 'bounties.game_id')
            ->select('session_reviews.id', 'session_reviews.reviewer_id', 'games.title as game_title' ,'session_reviews.rate', 'session_reviews.object_type', 'session_reviews.description', 'session_reviews.recommend', 'session_reviews.created_at')
            ->where('object_type', Consts::OBJECT_TYPE_BOUNTY)
            ->where('session_reviews.user_id', $userId);

        return $userReviewSession
            ->union($userReviewBounty)
            ->orderBy('id', 'desc')
            ->paginate(array_get($params, 'limit', Consts::DEFAULT_PER_PAGE));
    }

    public function getMyReviews($params)
    {
        return SessionReview::with(['userReview'])
            ->where('user_id', Auth::id())
            ->paginate(array_get($params, 'limit', Consts::DEFAULT_PER_PAGE));
    }

    public function getMyFollowings($params)
    {
        return UserFollowing::with(['idol'])
            ->where('user_id', $params['user_id'])
            ->where('is_following', Consts::TRUE)
            ->orderBy('updated_at', 'asc')
            ->paginate(array_get($params, 'limit', Consts::DEFAULT_PER_PAGE));
    }

    public function getUserFollowers($params)
    {
        return UserFollowing::with(['fan'])
            ->where('following_id', $params['user_id'])
            ->where('is_following', Consts::TRUE)
            ->orderBy('updated_at', 'asc')
            ->paginate(array_get($params, 'limit', Consts::DEFAULT_PER_PAGE));
    }

    public function getInfoMyIdolByFollowingId($userId, $followingId)
    {
        return UserFollowing::with(['idol'])->where('user_id', $userId)->where('following_id', $followingId)->first();
    }

    public function getInfoIdolAndFanByFollowingId($userId, $followingId) {
        return UserFollowing::with(['idol', 'fan'])->where('user_id', $userId)->where('following_id', $followingId)->first();
    }

    public function addOrRemoveFollow($followingId, $isFollowing = Consts::TRUE)
    {
        $followerId = Auth::id();
        if ($followerId === $followingId) {
            throw new InvalidRequestException('exceptions.cannot_follow_yoursefl');
        }

        $userBlockList = $this->getUserBlocklists($followerId);
        if ($isFollowing && in_array($followingId, $userBlockList->toArray())) {
            throw new InvalidRequestException('exceptions.cannot_follow_block_user');
        }

        $follow = UserFollowing::firstOrNew(['user_id' => $followerId, 'following_id' => $followingId]);
        $follow->is_following = $isFollowing;
        $follow->save();

        if ($isFollowing) {
            $isFriend = UserFollowing::where('user_id', $followingId)
                ->where('following_id', $followerId)
                ->where('is_following', Consts::TRUE)
                ->exists();
            $this->sendNotificationToFollower($follow, $isFriend);
            // $this->sendNotificationToFan($follow, $isFriend);

            // CollectTaskingJob::dispatch($followerId, Tasking::FOLLOW_USER);
            event(new UserFollowingUpdated($this->getInfoMyIdolByFollowingId($followerId, $followingId)));
            if ($isFriend) {
                event(new UserAddFriendUpdated($followerId, $this->getInfoIdolAndFanByFollowingId($followerId, $followingId)));
                event(new UserAddFriendUpdated($followingId, $this->getInfoIdolAndFanByFollowingId($followerId, $followingId)));
            }
        } else {
            event(new UserUnfollowUpdated($this->getInfoMyIdolByFollowingId($followerId, $followingId)));
            event(new UserUnfriendUpdated($followerId, $this->getInfoIdolAndFanByFollowingId($followerId, $followingId)));
            event(new UserUnfriendUpdated($followingId, $this->getInfoIdolAndFanByFollowingId($followerId, $followingId)));
        }

        dispatch(new CalculateUserFollow($followerId, $followingId));

        return $follow;
    }

    private function sendNotificationToFollower($follow, $isFriend)
    {
        $notificationParams = [
            'user_id' => $follow->following_id,
            'type' => Consts::NOTIFY_TYPE_NEW_FOLLOWER,
            'message' => $isFriend ? Consts::NOTIFY_NEW_FOLLOW_FRIEND : Consts::NOTIFY_NEW_FOLLOW,
            'props' => [],
            'data' => ['user' => (object) ['id' => $follow->user_id]]
        ];
        $this->fireNotification(Consts::NOTIFY_TYPE_OTHER, $notificationParams);
    }

    private function sendNotificationToFan($follow, $isFriend)
    {
        $notificationParams = [
            'user_id' => $follow->user_id,
            'type' => Consts::NOTIFY_TYPE_NEW_FOLLOWING,
            'message' => $isFriend ? Consts::NOTIFY_NEW_FOLLOWING_FRIEND : Consts::NOTIFY_NEW_FOLLOWING,
            'props' => [],
            'data' => ['user' => (object) ['id' => $follow->following_id]]
        ];
        $this->fireNotification(Consts::NOTIFY_TYPE_OTHER, $notificationParams);
    }

    public function verifyChangeEmail($input)
    {
        $changeEmailHistory = ChangeEmailHistory::where('email_verification_code', $input['verify_code'])
            ->where('email_verified', Consts::FALSE)
            ->orderBy('id', 'desc')
            ->first();
        if (!$changeEmailHistory) {
            throw new ChangeEmailException('auth.verify.error_code');
        }
        if ($changeEmailHistory->isEmailVerificationCodeExpired()) {
            throw new ChangeEmailException('auth.verify.expired_code');
        }

        $newEmail = $changeEmailHistory->new_email;

        $user = User::find($changeEmailHistory->user_id);

        $oldEmail = $user->email;

        $user->email = $newEmail;
        $user->save();

        $changeEmailHistory->email_verified = Consts::TRUE;
        $changeEmailHistory->email_verification_code = null;
        $changeEmailHistory->email_verification_code_created_at = null;
        $changeEmailHistory->save();
        $changeEmailHistory->delete();

        event(new UserUpdated($user->id));
        event(new EmailChanged($user->id, 'success'));

        return $changeEmailHistory;
    }

    public function updateSettings($userId, $params)
    {
        $settings = UserSetting::firstOrNew(['id' => $userId]);
        $settings = $this->saveData($settings, $params);

        // save new info of user to cache.
        $userData = ChatUtils::getUserDataToCache($userId);
        ChatUtils::updateChannelMembers($userData);

        // save new info of user to cache for community.
        CommunityUtils::updateAllChannelMembers($userId);
        event(new UserUpdated($userId));
        event(new UserProfileUpdated($userId));
        event(new UserSettingsUpdated($userId));

        return $settings;
    }

    public function getUserSettings($userId)
    {
        return UserSetting::where(['id' => $userId])->first();
    }

    public function updateProfile($userId, $params)
    {
        $user = User::find($userId);

        $oldUsername = $user->username;

        $user->languages = array_get($params, 'languages', $user->languages);
        $user->description = array_get($params, 'description', $user->description);
        $user->avatar = array_get($params, 'avatar', $user->avatar);
        $user->sex = array_get($params, 'sex', $user->sex);
        $user->dob = array_get($params, 'dob', Carbon::createFromFormat('Y-m-d', $user->dob)->format('d/m/Y'));
        $user->username = array_get($params, 'username', $user->username);

        if ($user->isDirty()) {
            $user->save();
        }

        event(new UserUpdated($userId));
        event(new UserProfileUpdated($userId));

        // save new info of user to cache for direct message.
        $userData = ChatUtils::getUserDataToCache($userId);
        ChatUtils::updateChannelMembers($userData);

        // save new info of user to cache for community.
        CommunityUtils::updateAllChannelMembers($userId);

        if ($user->username !== $oldUsername) {
            $data = [
                'user_id'        => $user->id,
                'old_username'   => $oldUsername,
                'username'       => $user->username
            ];
            event(new UsernameChanged($data));
        }

        return $user;
    }

    public function getGamelancerInfo($userId)
    {
        return GamelancerInfo::with([
                'gameProfile' => function ($query) {
                    $query->with(['game']);
                },
            ])
            ->where('user_id', $userId)
            ->first();
    }

    public function deleteSocialNetwork($id)
    {
        $deleteLink = UserSocialNetwork::where('id', $id)->delete();
        event(new UserUpdated(Auth::id()));
        event(new UserProfileUpdated(Auth::id()));
        return $deleteLink;
    }

    public function getInvitationCode()
    {
        return InvitationCode::value('code');
    }

    public function getUserScheduler($userId, $includeBooked = true)
    {
        $user = User::find($userId);
        $availableTimes = $user->availableTimes->map(function ($item) {
            return [
                'from' => $item->from,
                'to' => $item->to
            ];
        });

        $bookedSlots = $this->getSessionBookedSlots($userId, $includeBooked)->map(function ($item) {
            return [
                'quantity' => $item->quantity,
                'schedule_at' => $item->schedule_at
            ];
        });

        return [
            'booked_slots' => $bookedSlots,
            'available_times' => $user->availableTimes
        ];
    }

    public function getSessionBookedSlots($userId, $includeBooked = true)
    {
        $statusList = [
            Consts::SESSION_STATUS_STARTING,
            Consts::SESSION_STATUS_ACCEPTED,
            Consts::SESSION_STATUS_RUNNING
        ];

        if ($includeBooked) {
            $statusList[] = Consts::SESSION_STATUS_BOOKED;
        }

        return Session::whereNotNull('schedule_at')
            ->where('gamelancer_id', $userId)
            ->whereIn('status', $statusList)
            // ->whereHas('gameOffer', function ($query) {
            //     $query->where('type', Consts::GAME_TYPE_HOUR);
            // })
            ->get();
    }

    public function getSessionBookedSlotsAsUser($userId)
    {
        $statusList = [
            Consts::SESSION_STATUS_STARTING,
            Consts::SESSION_STATUS_ACCEPTED,
            Consts::SESSION_STATUS_RUNNING
        ];

        return Session::whereNotNull('schedule_at')
            ->where('claimer_id', $userId)
            ->whereIn('status', $statusList)
            ->get();
    }

    public function report($input)
    {
        $userId = Auth::id();
        if ($this->checkReportExisted($userId, $input['report_user_id'])) {
            throw new InvalidRequestException('exceptions.already_report_user');
        }

        return UserReport::create([
            'user_id' => $userId,
            'report_user_id' => $input['report_user_id'],
            'reason_id' => $input['reason_id'],
            'details' => array_get($input, 'details'),
            'status' => Consts::REPORT_STATUS_PROCESSING
        ]);
    }

    public function checkReportExisted($userId, $reportUserId)
    {
        return UserReport::where('user_id', $userId)
            ->where('report_user_id', $reportUserId)
            ->where('status', Consts::REPORT_STATUS_PROCESSING)
            ->exists();
    }

    public function createInterestsGames($params)
    {
        $userInterestsGames = [];

        foreach ($params as $param) {

            $existedGame = $this->getExistedInterestGame($param);
            if ($existedGame) {
                $param['id'] = $existedGame->id;
                return $this->updateInterestGame($param);
            }

            $userInterestsGame = UserInterestsGame::create(
                [
                    'user_id'         => Auth::id(),
                    'platform_id'     => array_get($param, 'platform_id'),
                    'game_id'         => array_get($param, 'game_id'),
                    'game_name'       => array_get($param, 'game_name')
                ]
            );

            $userInterestsGame->createOrUpdateUserInterestsGameMatchServers(array_get($param, 'server_ids'));

            $userInterestsGames[] = $userInterestsGame;
        }

        return $userInterestsGames;
    }

    public function getExistedInterestGame($param)
    {
        return UserInterestsGame::where(
            [
                'user_id' => Auth::id(),
                'game_id' => array_get($param, 'game_id'),
                'game_name' => array_get($param, 'game_name')
            ]
        )
        ->when(isset($param['id']), function ($query) use ($param) {
            $query->where('id', '<>', $param['id']);
        })->first();
    }

    public function preUpdateInterestGame($params)
    {
        $existedGame = $this->getExistedInterestGame($params);
        if ($existedGame) {
            throw new InvalidRequestException('exceptions.duplicated_game_name');
        }
        return $this->updateInterestGame($params);
    }

    public function updateInterestGame($params)
    {
        $userInterestsGame = UserInterestsGame::where('id', array_get($params, 'id'))
            ->where('user_id', Auth::id())
            ->where('game_id', array_get($params, 'game_id'))
            ->first();

        if (!$userInterestsGame) {
            throw new InvalidRequestException('exceptions.invalid_perform_update_action');
        }

        $userInterestsGame->game_name = array_get($params, 'game_name');
        $userInterestsGame->platform_id = array_get($params, 'platform_id');
        $userInterestsGame->save();

        $userInterestsGame->createOrUpdateUserInterestsGameMatchServers(array_get($params, 'server_ids'));

        return $userInterestsGame;
    }

    public function deleteInterestsGame($userInterestsGameId)
    {
        $userInterestsGame = UserInterestsGame::find($userInterestsGameId);

        $userInterestsGame->userInterestsGameMatchServer()->delete();

        $userInterestsGame->delete();

        return $userInterestsGame;
    }

    public function getInterestsGames($params)
    {
        $userId = array_get($params, 'user_id', Auth::id());
        return UserInterestsGame::where('user_id', $userId)
            ->paginate(array_get($params, 'limit', Consts::DEFAULT_PER_PAGE));
    }

    private function sendVerifyCodeChangeUsername($user, $changeUsernameHistory)
    {
        if ($user->isAccountVerified() && $user->hasVerifiedEmail()) {
            return Mail::queue(new VerificationChangeUsernameQueue($changeUsernameHistory, $user->email, Consts::DEFAULT_LOCALE));
        }

        $allowPhoneNumber = PhoneUtils::allowSmsNotification((object) ['phone_country_code' => $user->new_phone_country_code]);
        if (!$allowPhoneNumber) {
            throw new ChangePhoneNumberException('exceptions.phone_not_supported');
        }

        SendSmsNotificationJob::dispatch(
            $user,
            Consts::NOTIFY_SMS_USERNAME_CODE,
            ['code' => $changeUsernameHistory->verification_code]
        );

        return true;
    }

    public function verifyChangeUsername($verifyCode)
    {
        $changeUsernameHistory = ChangeUsernameHistory::where('verification_code', $verifyCode)
            ->where('verified', Consts::FALSE)
            ->orderBy('id', 'desc')
            ->first();
        if (!$changeUsernameHistory) {
            throw new ChangeUsernameException('auth.verify.error_code');
        }
        if ($changeUsernameHistory->isVerificationCodeExpired()) {
            throw new ChangeUsernameException('auth.verify.expired_code');
        }

        $newUsername = $changeUsernameHistory->new_username;

        $user = User::find($changeUsernameHistory->user_id);

        $oldUsername = $user->username;

        $user->username = $newUsername;
        $user->save();

        $changeUsernameHistory->verified = Consts::TRUE;
        $changeUsernameHistory->verification_code = null;
        $changeUsernameHistory->verification_code_created_at = null;
        $changeUsernameHistory->save();
        $changeUsernameHistory->delete();

        // update username for Mattermost
        $userData = ChatUtils::getUserDataToCache($userId);
        ChatUtils::updateUserInfo($userData);

        event(new UserUpdated($user->id));
        $data = [
            'user_id'        => $user->id,
            'old_username'   => $oldUsername,
            'username'       => $user->username
        ];
        event(new UsernameChanged($data));

        return $changeUsernameHistory;
    }

    public function verifyChangePhoneNumber($verifyCode)
    {
        $changePhoneNumberHistory = ChangePhoneNumberHistory::where('verification_code', $verifyCode)
            ->where('verified', Consts::FALSE)
            ->orderBy('id', 'desc')
            ->first();

        if (!$changePhoneNumberHistory) {
            throw new ChangePhoneNumberException('auth.verify.error_code');
        }

        if ($changePhoneNumberHistory->isVerificationCodeExpired()) {
            throw new ChangePhoneNumberException('auth.verify.expired_code');
        }

        $user = User::find($changePhoneNumberHistory->user_id);

        $user->phone_number = $changePhoneNumberHistory->new_phone_number;
        $user->phone_country_code = $changePhoneNumberHistory->new_phone_country_code;
        $user->save();

        $changePhoneNumberHistory->verified = Consts::TRUE;
        $changePhoneNumberHistory->verification_code = null;
        $changePhoneNumberHistory->verification_code_created_at = null;
        $changePhoneNumberHistory->save();
        $changePhoneNumberHistory->delete();

        event(new UserUpdated($user->id));

        event(new PhoneNumberChanged($user->id, 'success'));

        return $changePhoneNumberHistory;
    }

    private function validateChangeEmailOrPhoneOrUsername ($user)
    {
        if ($user->newEmail || $user->newUsername || $user->newPhoneNumber) {
            throw new ChangeConcurrentlyEmailOrPhoneOrUsernameException();
        }

        return true;
    }

    public function savePhoneForUser($phoneNumber)
    {
        $user = Auth::user();

        if ($user->phone_number && $user->hasVerifiedPhone()) {
            throw new PhoneNumberVerifiedException();
        }

        $confirmationCode = Utils::generateRandomString(Consts::VERIFY_CODE_LENGTH, Consts::VERIFY_CODE_STRING);

        $user->phone_number = $phoneNumber;
        $user->phone_country_code = PhoneUtils::getCountryCodeByFullPhoneNumber($phoneNumber);
        $user->phone_verify_code = $confirmationCode;
        $user->phone_verify_created_at = Carbon::now();
        $user->save();

        event(new UserUpdated($user->id));

        return $user;
    }

    public function checkPasswordValid($password)
    {
        if (Hash::check($password, Auth::user()->password)) {
            return [];
        }

        throw ValidationException::withMessages([
            'password' => [__('exceptions.password.invalid')]
        ]);
    }

    public function getUserTaskings($type = null)
    {
        $userId = Auth::id();
        $collecting = RankingUtils::getTaskingCollected($userId);

        $taskings = empty($type) ? Tasking::all() : Tasking::where('type', $type)->get();
        $taskings->transform(function ($tasking) use ($collecting) {
            return $tasking->getUserTasks($collecting);
        });

        $collectedReward = RankingUtils::getTaskingRewardCollected($userId);

        $taskingRewards = empty($type) ? TaskingReward::all() : TaskingReward::where('type', $type)->get();
        $taskingRewards->transform(function ($tasking) use ($collectedReward) {
            return $tasking->toData($collectedReward);
        });

        $dailyCheckins = $this->getDailyCheckins();

        return [
            'taskings'          => $taskings,
            'rewards'           => $taskingRewards,
            'daily_checkins'    => $dailyCheckins
        ];
    }

    private function getDailyCheckins()
    {
        $userId = Auth::id();
        $userRanking = UserRanking::firstOrNew(['user_id' => $userId]);

        // Only case for new user works this task first time.
        if (!$userRanking->checkin_milestone) {
            return $this->createNewCheckinMilestone($userRanking);
        }

        $todayCheckin = RankingUtils::getTodayCheckinSetting($userRanking);
        // Today is over max day of milestone
        if (!$todayCheckin) {
            $maxDayMilestone = DailyCheckin::where('user_id', $userId)
                ->where('milestone', $userRanking->checkin_milestone)
                ->get()
                ->count();
            $isAlreadyCheckinContinuous = RankingUtils::isAlreadyCheckinContinuous($userRanking, $maxDayMilestone);
            return $this->createNewCheckinMilestone($userRanking, !$isAlreadyCheckinContinuous);
        }

        // Today is in milestone
        $milestoneData = $this->getCheckinMilestoneData($userRanking, $todayCheckin);
        $isContinuous = RankingUtils::isContinuousDailyCheckin($milestoneData, $todayCheckin);
        if (!$isContinuous) {
            return $this->createNewCheckinMilestone($userRanking, true);
        }

        return DailyCheckin::where('user_id', $userId)
            ->where('milestone', $userRanking->checkin_milestone)
            ->get()
            ->map(function ($record) {
                return $record->toData();
            });
    }

    private function createNewCheckinMilestone($userRanking, $isResetMilestone = false)
    {
        $newMilestone = RankingUtils::initializeNewMilestoneDailyCheckin($userRanking)
            ->map(function ($record) {
                return $record->toData();
            });

        if ($isResetMilestone) {
            $this->fireResetDailyCheckinNotification($userRanking->user_id);
        }

        return $newMilestone;
    }

    public function collectStepIntroTask($step)
    {
        $userId = Auth::id();
        $ranking = UserRanking::firstOrCreate([
            'user_id' => $userId
        ]);

        $ranking->intro_step = $step;
        $ranking->save();

        $data = ['currency' => Consts::CURRENCY_EXP, 'value' => 0];

        if ($ranking->intro_step >= Consts::TOTAL_INTRO_STEPS) {
            $data['award'] = $this->collectAndAwardIntroTask(Auth::user());
        }

        return $data;
    }

    private function collectAndAwardIntroTask($user)
    {
        $tasking = Tasking::where('code', Tasking::EXPLORE_PLATFORM)->first();

        $this->collectUserTasking($tasking->id);
        RankingUtils::awardForUser($user->id, Consts::CURRENCY_EXP, $tasking->exp);

        return ['currency' => Consts::CURRENCY_EXP, 'quantity' => $tasking->exp];
    }

    public function collectUserTasking($taskingId, $userId = null)
    {
        $userId = $userId ?: Auth::id();
        if (RankingUtils::overThresholdTasking($userId, $taskingId)) {
            return false;
        }

        CollectingTasking::create([
            'user_id'       => $userId,
            'tasking_id'    => $taskingId,
            'collected_at'  => Utils::currentMilliseconds()
        ]);

        event(new TaskCollected($userId, $taskingId));

        RankingUtils::fireNotification($userId, $taskingId);

        return true;
    }

    public function claimTasking($type, $levelReward)
    {
        $user   = Auth::user();
        $reward = RankingUtils::validateClaimingTasking($user->id, $type, $levelReward);

        CollectingTaskingReward::create([
            'user_id' => $user->id,
            'tasking_reward_id' => $reward->id
        ]);

        RankingUtils::awardForUser($user->id, $reward->currency, $reward->quantity);
        return true;
    }

    public function collectDailyCheckin($checkinId)
    {
        $checkin = DailyCheckin::findOrFail($checkinId);
        if ($checkin->checked_at) {
            throw new InvalidRequestException('exceptions.invalid_collect_daily_checkin');
        }

        $userId = Auth::id();
        $userRanking = UserRanking::firstOrNew(['user_id' => $userId]);

        $todayCheckin = RankingUtils::getTodayCheckinSetting($userRanking);
        if (!$todayCheckin || $todayCheckin->day !== $checkin->day) {
            throw new InvalidRequestException('exceptions.invalid_collect_daily_checkin');
        }

        $milestoneData = $this->getCheckinMilestoneData($userRanking, $checkin);

        $isContinuous = RankingUtils::isContinuousDailyCheckin($milestoneData, $checkin);
        if (!$isContinuous) {
            throw new InvalidRequestException('exceptions.invalid_collect_daily_checkin');
        }

        $checkin->checked_at = now();
        $checkin->save();

        RankingUtils::awardForUser($userId, Consts::CURRENCY_EXP, $checkin->exp);
        event(new DailyCheckinCollected($userId, $checkin->toData()));

        return true;
    }

    private function getCheckinMilestoneData($userRanking, $checkin)
    {
        return DailyCheckin::where('user_id', $userRanking->user_id)
            ->where('milestone', $userRanking->checkin_milestone)
            ->where('day', '<=', $checkin->day)
            ->get();
    }

    private function fireResetDailyCheckinNotification($userId)
    {
        $params = [
            'user_id'   => $userId,
            'type'      => Consts::NOTIFY_TYPE_TASKING_DAILY_CHECKIN,
            'message'   => Consts::MESSAGE_NOTIFY_TASKING_RESET_DAILY_CHECKIN,
            'data'      => [
                'user'      => (object) ['id' => $userId],
                'mailable'  => null
            ]
        ];
        SendSystemNotification::dispatch(Consts::NOTIFY_TYPE_TASKING_DAILY_CHECKIN, $params)
            ->onQueue(Consts::QUEUE_NOTIFICATION);
    }

    public function sendOtpCode($throwException = true)
    {
        $user = Auth::user();
        $confirmationCode = Utils::generateRandomString(Consts::VERIFY_CODE_LENGTH, Consts::VERIFY_CODE_STRING);
        OtpUtils::initOtpCodeToCache($user->id, $confirmationCode);

        if ($user->email_verified) {
            return $this->sendOtpCodeViaEmail($user, $confirmationCode);
        }

        if ($user->phone_verified && PhoneUtils::allowSmsNotification($user)) {
            return $this->sendOtpCodeViaPhone($user, $confirmationCode);
        }

        if ($throwException) {
            throw new AccountNotActivedException('exceptions.user_not_activated');
        }
    }

    public function confirmOtpCode($confirmationCode, $delete = true)
    {
        $user = Auth::user();
        $result = OtpUtils::confirmOtpCode($user->id, $confirmationCode, $delete);
        return $result;
    }

    private function sendOtpCodeViaEmail($user, $confirmationCode)
    {
        Mail::queue(new OtpMailQueue($user, Consts::DEFAULT_LOCALE, $confirmationCode));
    }

    private function sendOtpCodeViaPhone($user, $confirmationCode)
    {
        SendSmsNotificationJob::dispatch(
            $user,
            Consts::NOTIFY_SMS_CONFIRMATION_CODE,
            ['code' => $confirmationCode]
        );
    }

    public function resetUserRanking ()
    {
        if (Utils::isProduction()) {
            return;
        }

        $userId = Auth::id();
        CollectingTasking::where('user_id', $userId)->delete();
        CollectingTaskingReward::where('user_id', $userId)->delete();
        UserRanking::where('user_id', $userId)->delete();
    }

    public function getUnlockSecurityType()
    {
        // $user = Auth::user();
        // if (!$user->socialUser) {
        //     return [
        //         'is_verified' => true,
        //         'unlock_by' => Consts::SECURITY_UNLOCK_TYPE_PASSWORD
        //     ];
        // }

        // if ($user->email_verified) {
        //     $this->sendOtpCode(false);
        //     return [
        //         'is_verified' => true,
        //         'unlock_by' => Consts::SECURITY_UNLOCK_TYPE_EMAIL
        //     ];
        // }

        // if ($user->phone_verified && PhoneUtils::allowSmsNotification($user)) {
        //     $this->sendOtpCode(false);
        //     return [
        //         'is_verified' => true,
        //         'unlock_by' => Consts::SECURITY_UNLOCK_TYPE_PHONE
        //     ];
        // }

        // return [
        //     'is_verified' => false
        // ];
    }

    public function getListFriend($exceptUserIds = [], $params = [])
    {
        $friendsIds = $this->getFriendsId();
        $filterFriend = array_diff($friendsIds, $exceptUserIds);
        $searchKey = array_get($params, 'search_key');

        return User::join('user_settings', 'user_settings.id', 'users.id')
            ->when($searchKey, function ($query) use ($searchKey) {
                $query->where('users.username', 'like', '%' . $searchKey . '%');
            })
            ->whereIn('users.id', $filterFriend)
            ->select('users.id', 'users.avatar', 'users.sex', 'users.username', 'users.user_type', 'user_settings.online as online_setting')
            ->get();
    }

    public function getUsersExisted($params)
    {
        $data = array_get($params, 'data', []);

        if (empty($data)) {
            return null;
        }

        return User::join('user_settings', 'user_settings.id', 'users.id')
            ->where(function ($query) use ($data) {
                $query->whereIn('users.email', $data)
                    ->orWhereIn('users.phone_number', $data);
            })
            ->where('users.id', '<>', Auth::id())
            ->select('users.*', 'user_settings.online as online_setting')
            ->limit(100)
            ->get()
            ->transform(function ($record) {
                return [
                    'id'                => $record->id,
                    'email'             => $record->email,
                    'phone_number'      => $record->phone_number,
                    'username'          => $record->username,
                    'avatar'            => $record->avatar,
                    'user_type'         => $record->user_type,
                    'online_setting'    => $record->online_setting ?? Consts::TRUE
                ];
            });
    }

    public function getMyBlockList($request)
    {
        $userId = Auth::id();
        return UserBlockList::with(['userInfo'])
            ->where('user_id', $userId)
            ->where('is_blocked', Consts::TRUE)
            ->paginate(array_get($request, 'limit', Consts::DEFAULT_PER_PAGE));
    }

    public function addOrRemoveBlock($blockedUserId, $isBlocked = Consts::TRUE)
    {
        $userId = Auth::id();
        $blockList = UserBlockList::firstOrNew(['user_id' => $userId, 'blocked_user_id' => $blockedUserId]);
        $blockList->is_blocked = $isBlocked;
        $blockList->save();

        if ($isBlocked) {
            $this->addOrRemoveFollow($blockedUserId, Consts::FALSE);
            event(new UserBlocked($userId, $blockedUserId));
        } else {
            event(new UserUnblocked($userId, $blockedUserId));
        }

        event(new UserUpdated($userId));
        event(new UserProfileUpdated($userId));

        return $blockList;
    }

    public function getUserBlocklists($userID)
    {
        return UserBlockList::where('user_id', $userID)->where('is_blocked', Consts::TRUE)->pluck('blocked_user_id');
    }

    public function getRecentRoomGames($userId, $params = [])
    {
        return VoiceChatRoomUser::join('voice_chat_rooms', 'voice_chat_rooms.id', 'voice_chat_room_users.room_id')
            ->where('voice_chat_rooms.game_id', '<>', Consts::COMMUNITY_ROOM_CATEGORY_GAME_ID)
            ->where('voice_chat_room_users.user_id', $userId)
            ->orderBy('voice_chat_room_users.started_time', 'desc')
            ->get()
            ->unique('game_id')
            ->pluck('game_id')
            ->take(5);
    }

    public function sendLoginCode($phoneNumber)
    {
        $phoneExist = User::where('phone_number', $phoneNumber)->exists();
        if (!$phoneExist) {
            throw new InvalidDataException('exceptions.not_existed.phone_number');
        }

        $code = Utils::generateRandomString(Consts::VERIFY_CODE_LENGTH, Consts::VERIFY_CODE_STRING);
        OtpUtils::initLoginCodeToCache($phoneNumber, $code);
        SendSmsNotificationJob::dispatch(
            $phoneNumber,
            Consts::NOTIFY_SMS_APP_LOGIN_CODE,
            ['code' => $code]
        );
    }

    public function changeEmail($email)
    {
        $user = Auth::user();

        if ($user->email_verified) {
            return $this->changeNewEmail($user, $email);
        }

        if (Utils::removeUserAutoEmail($user->email)) {
            $changingHistory = ChangeEmailHistory::create([
                'user_id' => $user->id,
                'old_email' => $user->email,
                'new_email' => strtolower($email),
                'email_verified' => Consts::TRUE,
                'without_verified_account' => Consts::TRUE
            ]);
            $changingHistory->delete();
        }

        $oldEmail = $user->email;
        $code = Utils::generateRandomString(Consts::VERIFY_CODE_LENGTH, Consts::VERIFY_CODE_STRING);

        $user->email = strtolower($email);
        $user->email_verification_code = $code;
        $user->email_verification_code_created_at = Carbon::now();
        $user->save();

        Mail::queue(new VerificationMailQueue($user, Consts::DEFAULT_LOCALE));

        event(new UserUpdated($user->id));
        event(new EmailChanged($user->id, 'success'));

        return true;
    }

    private function changeNewEmail($user, $email)
    {
        // for case changed email but not verified -> remove all history for user
        ChangeEmailHistory::where('user_id', $user->id)->delete();

        $code = Utils::generateRandomString(Consts::VERIFY_CODE_LENGTH, Consts::VERIFY_CODE_STRING);
        ChangeEmailHistory::create([
            'user_id' => $user->id,
            'old_email' => $user->email,
            'new_email' => strtolower($email),
            'email_verified' => Consts::FALSE,
            'email_verification_code' => $code,
            'email_verification_code_created_at' => Carbon::now(),
            'without_verified_account' => Consts::FALSE
        ]);

        event(new UserUpdated($user->id));

        return true;
    }

    public function changePhone($params)
    {
        $user = Auth::user();
        if ($user->phone_verified) {
            return $this->changeNewPhone($user, $params);
        }

        if ($user->phone_number) {
            $changingHistory = ChangePhoneNumberHistory::create([
                'user_id' => $user->id,
                'old_phone_number' => $user->phone_number,
                'new_phone_number' => $params['phone_number'],
                'new_phone_country_code' => PhoneUtils::getCountryCodeByFullPhoneNumber($params['phone_number']),
                'phone_verified' => Consts::TRUE,
                'without_verified_account' => Consts::TRUE
            ]);
            $changingHistory->delete();
        }

        $code = Utils::generateRandomString(Consts::VERIFY_CODE_LENGTH, Consts::VERIFY_CODE_STRING);
        $user->phone_number = $params['phone_number'];
        $user->phone_country_code = PhoneUtils::getCountryCodeByFullPhoneNumber($params['phone_number']);
        $user->phone_verify_code = $code;
        $user->phone_verify_created_at = Carbon::now();
        $user->save();

        event(new UserUpdated($user->id));
        event(new PhoneNumberChanged($user->id, 'success'));

        return true;
    }

    private function changeNewPhone($user, $params)
    {
        // for case changed phone but not verified -> remove all history for user
        ChangePhoneNumberHistory::where('user_id', $user->id)->delete();

        $code = Utils::generateRandomString(Consts::VERIFY_CODE_LENGTH, Consts::VERIFY_CODE_STRING);
        ChangePhoneNumberHistory::create([
            'user_id' => $user->id,
            'old_phone_number' => $user->phone_number,
            'new_phone_number' => $params['phone_number'],
            'new_phone_country_code' => PhoneUtils::getCountryCodeByFullPhoneNumber($params['phone_number']),
            'phone_verified' => Consts::FALSE,
            'verification_code' => $code,
            'verification_code_created_at' => Carbon::now(),
            'without_verified_account' => Consts::FALSE
        ]);

        event(new UserUpdated($user->id));

        return true;
    }

    public function verifyEmail($params, $ip)
    {
        $user = User::find(Auth::id());
        if ($user->email === $params['email']) {
            return $this->verifyOriginEmail($user, $params['code'], $ip);
        }

        return $this->verifyChangingEmail($user, $params);
    }

    private function verifyOriginEmail($user, $code, $ip)
    {
        if ($user->email_verified) {
            throw new SecurityException('exceptions.auth.verify.email_verified');
        }

        if ($user->email_verification_code !== $code) {
            throw new SecurityException('exceptions.auth.verify.error_code');
        }

        if ($user->isEmailVerificationCodeExpired()) {
            throw new SecurityException('exceptions.auth.verify.expired_code');
        }

        $user->email_verified = Consts::TRUE;
        $user->email_verification_code = null;
        $user->email_verification_code_created_at = null;
        $user->save();

        if ($user->canActiveAndCreateBalanceForUser()) {
            $this->activeAndCreateBalanceForUser($user, $ip);
        }

        event(new UserUpdated($user->id));

        return true;
    }

    private function verifyChangingEmail($user, $params)
    {
        $changingHistory = ChangeEmailHistory::where('user_id', $user->id)
            ->where('new_email', $params['email'])
            ->where('email_verified', Consts::FALSE)
            ->where('email_verification_code', $params['code'])
            ->orderBy('id', 'desc')
            ->first();

        if (!$changingHistory) {
            throw new SecurityException('exceptions.auth.verify.error_code');
        }

        if ($changingHistory->isEmailVerificationCodeExpired()) {
            throw new SecurityException('exceptions.auth.verify.expired_code');
        }

        $oldEmail = $user->email;

        $user->email = $changingHistory->new_email;;
        $user->save();

        $changingHistory->email_verified = Consts::TRUE;
        $changingHistory->email_verification_code = null;
        $changingHistory->email_verification_code_created_at = null;
        $changingHistory->save();
        $changingHistory->delete();

        event(new UserUpdated($user->id));
        event(new EmailChanged($user->id, 'success'));

        return true;
    }

    public function verifyPhone($params, $ip)
    {
        $user = User::find(Auth::id());
        if ($user->phone_number === $params['phone_number']) {
            return $this->verifyOriginPhone($user, $params['code'], $ip);
        }

        return $this->verifyChangingPhone($params['code'], $params);
    }

    private function verifyOriginPhone($user, $code, $ip)
    {
        if ($user->phone_verified) {
            throw new SecurityException('exceptions.auth.verify.phone_verified');
        }

        if ($user->phone_verify_code !== $code) {
            throw new SecurityException('exceptions.auth.verify.error_code');
        }

        if ($user->isPhoneNumberVerificationCodeExpired()) {
            throw new SecurityException('exceptions.auth.verify.expired_code');
        }

        $user->phone_verified = Consts::TRUE;
        $user->phone_verify_code = null;
        $user->phone_verify_created_at = null;
        $user->save();

        if ($user->canActiveAndCreateBalanceForUser()) {
            $this->activeAndCreateBalanceForUser($user, $ip);
        }

        event(new UserUpdated($user->id));

        return true;
    }

    private function verifyChangingPhone($code, $params)
    {
        $userId = Auth::id();
        $changeHistory = ChangePhoneNumberHistory::where('user_id', $userId)
            ->where('new_phone_number', $params['phone_number'])
            ->where('verified', Consts::FALSE)
            ->where('verification_code', $code)
            ->first();

        if (!$changeHistory) {
            throw new SecurityException('auth.verify.error_code');
        }

        if ($changeHistory->isVerificationCodeExpired()) {
            throw new SecurityException('auth.verify.expired_code');
        }

        $user = User::find($userId);
        $user->phone_number = $changeHistory->new_phone_number;
        $user->phone_country_code = $changeHistory->new_phone_country_code;
        $user->save();

        $changeHistory->verified = Consts::TRUE;
        $changeHistory->verification_code = null;
        $changeHistory->verification_code_created_at = null;
        $changeHistory->save();
        $changeHistory->delete();

        event(new UserUpdated($user->id));
        event(new PhoneNumberChanged($user->id, 'success'));

        return true;
    }

    private function activeAndCreateBalanceForUser($user, $ip)
    {
        $device = $this->getCurrentDevice('', $user->id);
        $device->latest_ip_address = $ip;
        $device->save();

        $this->createNewUserBalance($user->id);
        AddKlaviyoMailList::dispatch($user);
    }

    public function sendEmailVerificationCode($user, $params)
    {
        if ($user->email_verified) {
            return $this->sendChangingEmailVerificationCode($user, $params['email']);
        }

        $trueEmail = Utils::removeUserAutoEmail($user->email);
        if (!$trueEmail) {
            throw new SecurityException('exceptions.auth.verify.not_register_email');
        }

        $code = Utils::generateRandomString(Consts::VERIFY_CODE_LENGTH, Consts::VERIFY_CODE_STRING);
        $user->email_verification_code = $code;
        $user->email_verification_code_created_at = Carbon::now();
        $user->save();

        Mail::queue(new VerificationMailQueue($user, Consts::DEFAULT_LOCALE));

        return true;
    }

    private function sendChangingEmailVerificationCode($user, $email)
    {
        $changingHistory = ChangeEmailHistory::where('user_id', $user->id)
            ->where('old_email', $user->email)
            ->where('new_email', $email)
            ->where('email_verified', Consts::FALSE)
            ->orderBy('id', 'desc')
            ->first();

        if (!$changingHistory) {
            throw new SecurityException('exceptions.auth.verify.email_invalid');
        }

        $code = Utils::generateRandomString(Consts::VERIFY_CODE_LENGTH, Consts::VERIFY_CODE_STRING);
        $changingHistory->email_verification_code = $code;
        $changingHistory->email_verification_code_created_at = Carbon::now();
        $changingHistory->save();

        Mail::queue(new VerificationChangeMailQueue($changingHistory, $user->username, Consts::DEFAULT_LOCALE));

        return true;
    }

    public function sendPhoneVerificationCode($user, $params)
    {
        if ($user->phone_verified) {
            return $this->sendChangingPhoneVerificationCode($user, $params['phone_number']);
        }

        if (!$user->phone_number) {
            throw new SecurityException('exceptions.auth.verify.not_register_phone');
        }

        $code = Utils::generateRandomString(Consts::VERIFY_CODE_LENGTH, Consts::VERIFY_CODE_STRING);
        $user->phone_verify_code = $code;
        $user->phone_verify_created_at = Carbon::now();
        $user->save();
        SendSmsNotificationJob::dispatch($user, Consts::NOTIFY_SMS_VERIFY_CODE);

        event(new UserUpdated($user->id));

        return true;
    }

    public function sendChangingPhoneVerificationCode($user, $phoneNumber)
    {
        $changingHistory = ChangePhoneNumberHistory::where('user_id', $user->id)
            ->where('old_phone_number', $user->phone_number)
            ->where('new_phone_number', $phoneNumber)
            ->where('verified', Consts::FALSE)
            ->orderBy('id', 'desc')
            ->first();

        if (!$changingHistory) {
            throw new SecurityException('exceptions.auth.verify.phone_number_invalid');
        }

        $code = Utils::generateRandomString(Consts::VERIFY_CODE_LENGTH, Consts::VERIFY_CODE_STRING);
        $changingHistory->verification_code = $code;
        $changingHistory->verification_code_created_at = Carbon::now();
        $changingHistory->save();

        SendSmsNotificationJob::dispatch(
            $phoneNumber,
            Consts::NOTIFY_SMS_PHONE_CODE,
            ['code' => $changingHistory->verification_code]
        );

        event(new UserUpdated($user->id));

        return true;
    }

    public function cancelChangingEmail()
    {
        $userId = Auth::id();
        $deleteChange = ChangeEmailHistory::where('user_id', $userId)
            ->where('email_verified', Consts::FALSE)
            ->whereNull('deleted_at')
            ->delete();

        event(new UserUpdated($userId));

        return $deleteChange;
    }

    public function cancelChangingPhone()
    {
        $userId = Auth::id();
        $deleteChange = ChangePhoneNumberHistory::where('user_id', $userId)
            ->where('verified', Consts::FALSE)
            ->whereNull('deleted_at')
            ->delete();

        event(new UserUpdated($userId));

        return $deleteChange;
    }

    public function getPlayingFriends($params)
    {
        $friendsIds = $this->getFriendsId();
        return VoiceChatRoomUser::select('users.id', 'users.avatar', 'users.username', 'users.sex', 'voice_chat_rooms.name as room_name', 'voice_chat_rooms.game_id as room_game_id')
            ->join('voice_chat_rooms', 'voice_chat_rooms.id', 'voice_chat_room_users.room_id')
            ->join('users', 'users.id', 'voice_chat_room_users.user_id')
            ->join('user_settings', 'user_settings.id', 'voice_chat_room_users.user_id')
            ->whereIn('voice_chat_room_users.user_id', $friendsIds)
            ->whereNull('voice_chat_room_users.ended_time')
            ->where('voice_chat_rooms.status', Consts::VOICE_ROOM_STATUS_CALLING)
            ->where('voice_chat_rooms.is_private', Consts::FALSE)
            ->where('user_settings.online', Consts::TRUE)
            ->limit(array_get($params, 'limit', Consts::DEFAULT_PER_PAGE))
            ->get();
    }

    private function getFriendsId()
    {
        $userId = Auth::id();
        return User::join('user_following as table1', 'table1.following_id', 'users.id')
            ->join('user_following as table2', 'table2.user_id', 'users.id')
            ->where('table1.user_id', $userId)
            ->where('table1.is_following', Consts::TRUE)
            ->where('table2.following_id', $userId)
            ->where('table2.is_following', Consts::TRUE)
            ->groupBy('table2.user_id')
            ->pluck('table2.user_id')
            ->toArray();
    }

    public function changePassword($password)
    {
        $user = User::find(Auth::id());
        $user->password = bcrypt($password);
        $user->save();

        Mail::queue(new ChangePasswordMail($user));

        event(new UserUpdated($user->id));

        return true;
    }

    public function changeUsername($username)
    {
        $user = User::find(Auth::id());
        $oldUsername = $user->username;

        $user->username = $username;
        $user->save();

        // update username for Mattermost
        $userData = ChatUtils::getUserDataToCache($user->id);
        ChatUtils::updateUserInfo($userData);

        // save new info of user to cache for community.
        CommunityUtils::updateAllChannelMembers($user->id);

        $data = [
            'user_id'        => $user->id,
            'old_username'   => $oldUsername,
            'username'       => $user->username
        ];
        event(new UsernameChanged($data));
        event(new UserUpdated($user->id));
        event(new UserProfileUpdated($user->id));

        return true;
    }

    public function getSuggestFriends($params)
    {
        $user = Auth::user();
        $limit = array_get($params, 'limit', Consts::DEFAULT_PER_PAGE);
        $friendsIds = $this->getFriendsId();

        // user has same phone region
        $exceptUserIds = array_merge($friendsIds, [$user->id]);
        $sameRegion = User::whereNotIn('id', $exceptUserIds)
            ->whereNotNull('phone_country_code')
            ->where('phone_country_code', $user->phone_country_code)
            ->take($limit)
            ->pluck('id')
            ->toArray();

        if (count($sameRegion) >= $limit) {
            return $this->getListFriendInfo($sameRegion);
        }

        // user has same languages
        $exceptUserIds = array_merge($exceptUserIds, $sameRegion);
        $sameLanguages = User::whereNotIn('id', $exceptUserIds)
            ->where(function ($q) use ($user) {
                foreach($user->languages as $key => $language) {
                    if ($key === 0) {
                        $q->where('languages', 'like', "%{$language}%");
                    }
                    $q->orWhere('languages', 'like', "%{$language}%");
                }
            })
            ->take($limit - count($sameRegion))
            ->pluck('id')
            ->toArray();

        $userIds = array_merge($sameRegion, $sameLanguages);
        if (count($userIds) >= $limit) {
            return $this->getListFriendInfo($userIds);
        }

        // user has same recent joined rooms
        $exceptUserIds = array_merge($exceptUserIds, $sameLanguages);
        $userRecentRoomGame = $this->getRecentRoomGames($user->id);
        $sameRoomGame = VoiceChatRoomUser::join('voice_chat_rooms', 'voice_chat_rooms.id', 'voice_chat_room_users.room_id')
            ->whereNotIn('voice_chat_room_users.user_id', $exceptUserIds)
            ->whereIn('voice_chat_rooms.game_id', $userRecentRoomGame)
            ->orderBy('voice_chat_room_users.started_time', 'desc')
            ->get()
            ->unique('user_id')
            ->pluck('user_id')
            ->take($limit - count($userIds))
            ->toArray();

        $userIds = array_merge($userIds, $sameRoomGame);
        return $this->getListFriendInfo($userIds);
    }

    private function getListFriendInfo($userIds)
    {
        return User::withoutAppends()
            ->join('user_settings', 'user_settings.id', 'users.id')
            ->whereIn('users.id', $userIds)
            ->select('users.id', 'users.username', 'users.sex', 'users.avatar', 'user_settings.online as online_setting')
            ->get();
    }

    public function checkEmailExists($email)
    {
        return User::where('email', $email)->exists();
    }

    public function checkPhoneNumberExists($params)
    {
        return User::where('phone_number', $params['phone_number'])->exists();
    }

    public function sendEmailAuthorizationCode($params)
    {
        $user = $this->getUserWithEmailOrPhone($params);

        $code = Utils::generateRandomString(Consts::VERIFY_CODE_LENGTH, Consts::VERIFY_CODE_STRING);
        OtpUtils::initAuthorizationCodeToCache($user->id, $code);

        $this->sendAuthorizationCodeEmail($user, $code);

        return $user;
    }

    public function sendPhoneAuthorizationCode($params)
    {
        $user = $this->getUserWithEmailOrPhone($params);
        if (!PhoneUtils::allowSmsNotification($user)) {
            throw new PhoneNumberNotSupportedException();
        }

        $code = Utils::generateRandomString(Consts::VERIFY_CODE_LENGTH, Consts::VERIFY_CODE_STRING);
        OtpUtils::initAuthorizationCodeToCache($user->id, $code);

        $this->sendAuthorizationCodeSms($user, $code);

        return $user;
    }

    private function sendAuthorizationCodeSms($user, $code)
    {
        return SendSmsNotificationJob::dispatch(
            $user,
            Consts::NOTIFY_SMS_AUTHORIZATION_CODE,
            ['code' => $code]
        );
    }

    private function sendAuthorizationCodeEmail($user, $code)
    {
        return Mail::queue(new AuthorizationMailQueue($user, Consts::DEFAULT_LOCALE, $code));
    }

    public function getUserWithEmailOrPhone ($params)
    {
        $email = array_get($params, 'email');
        $phone = array_get($params, 'phone_number');
        return User::withoutAppends()
            ->when($email, function ($query) use ($email) {
                $query->where('email', $email);
            })
            ->when($phone, function ($query) use ($phone) {
                $query->orWhere('phone_number', $phone);
            })
            ->first();
    }

    public function getAuthorizationUsers($params)
    {
        $email = array_get($params, 'email');
        $phone = array_get($params, 'phone_number');
        return User::withoutAppends()
            ->with(['socialUser'])
            ->where('email', $email)
            ->orWhere(function ($query) use ($phone) {
                $query->where('phone_number', $phone)
                    ->whereNotNull('phone_number');
            })
            ->get();
    }

    public function attachSocialAccount($params, $providerUser)
    {
        $userId = array_get($params, 'user_id');
        $code = array_get($params, 'code');
        if (!OtpUtils::confirmAuthorizationCode($userId, $code)) {
            throw new InvalidCodeException();
        }

        return SocialUser::updateOrCreate(
            ['user_id' => $userId, 'provider' => array_get($params, 'provider')],
            [
                'provider_id' =>  $providerUser->id,
                'email' => !empty($providerUser->email) ? $providerUser->email : null,
                'phone_number' => !empty($providerUser->phone) ? PhoneUtils::formatPhoneNumber($providerUser->phone) : null
            ]
        );
    }

    public function deleteUser()
    {
        $userId = Auth::id();
        Community::where('creator_id', $userId)->update(['status' => Consts::COMMUNITY_STATUS_DELETED, 'inactive_at' => Carbon::now()]);
        User::where('id', $userId)->update(['status' => Consts::USER_DELETED, 'deleted_at' => Carbon::now()]);
        event(new UserProfileUpdated($userId));
        CommunityUtils::updateAllChannelMembers($userId);
        return true;
    }

    public function getCommunityAvailable($userId)
    {
        return DB::table('community_members')
            ->select('community_members.*', 'communities.deleted_at', 'communities.status as community_status')
            ->join('communities', 'communities.id', 'community_members.community_id')
            ->whereNull('communities.deleted_at')
            ->whereNull('community_members.deleted_at')
            ->where('communities.status', Consts::COMMUNITY_STATUS_ACTIVE)
            ->where('community_members.user_id', $userId)
            ->get()->pluck('community_id');
    }

    // ======================== API VERSION 2 ========================
    public function sendEmailOtpCode($throwException = true)
    {
        $user = Auth::user();
        $confirmationCode = Utils::generateRandomString(Consts::VERIFY_CODE_LENGTH, Consts::VERIFY_CODE_STRING);
        OtpUtils::initEmailOtpCodeToCache($user->id, $confirmationCode);
        if ($user->email_verified) {
            return $this->sendOtpCodeViaEmail($user, $confirmationCode);
        }

        if ($throwException) {
            throw new AccountNotActivedException('exceptions.user_not_activated');
        }
    }

    public function sendPhoneOtpCode($throwException = true)
    {
        $user = Auth::user();
        $confirmationCode = Utils::generateRandomString(Consts::VERIFY_CODE_LENGTH, Consts::VERIFY_CODE_STRING);
        OtpUtils::initPhoneOtpCodeToCache($user->id, $confirmationCode);

        if ($user->phone_verified && PhoneUtils::allowSmsNotification($user)) {
            return $this->sendOtpCodeViaPhone($user, $confirmationCode);
        }

        if ($throwException) {
            throw new AccountNotActivedException('exceptions.user_not_activated');
        }
    }

    public function confirmEmailOtpCode($confirmationCode, $delete = true)
    {
        $user = Auth::user();
        $confirmationCode = OtpUtils::confirmEmailOtpCode($user->id, $confirmationCode, $delete);
        if (!$confirmationCode) {
            throw new SecurityException('exceptions.auth.verify.error_otp_code');
        }
        return $confirmationCode;
    }

    public function confirmPhoneOtpCode($confirmationCode, $delete = true)
    {
        $user = Auth::user();
        $confirmationCode = OtpUtils::confirmPhoneOtpCode($user->id, $confirmationCode, $delete);
        if (!$confirmationCode) {
            throw new SecurityException('exceptions.auth.verify.error_otp_code');
        }
        return $confirmationCode;
    }

    public function getProviderUser($request)
    {
        $provider = $request->provider;
        $token = $request->token;
        $openId = (isset($request->open_id)) ? $request->open_id : null;
        switch ($provider) {
            case Consts::PROVIDER_TIKTOK:
                $providerUser = Socialite::with($provider)->userFromTokenAndOpenId($token, $openId);
                break;
            default:
                $providerUser = Socialite::with($provider)->userFromToken($token);
                break;
        }
        return $providerUser;
    }
}
