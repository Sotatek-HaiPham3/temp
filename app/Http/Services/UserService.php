<?php

namespace App\Http\Services;

use App\Consts;

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
use App\Jobs\CalculateUserFollow;
use App\Jobs\PushAcountInfoHubSpotJob;
use App\Jobs\GenerateInvitationCodeJob;
use App\Jobs\AddKlaviyoMailList;
use App\Jobs\CollectTaskingJob;
use App\Jobs\SendSmsNotificationJob;
use App\Mails\VerificationChangeMailQueue;
use App\Mails\VerificationChangeUsernameQueue;
use App\Mails\VerificationChangePhoneNumberQueue;
use App\Mails\VerificationMailQueue;
use App\Mails\OtpMailQueue;
use App\Exceptions\Reports\ChangeEmailException;
use App\Exceptions\Reports\ChangeUsernameException;
use App\Exceptions\Reports\ChangePhoneNumberException;
use App\Exceptions\Reports\InvalidRequestException;
use App\Exceptions\Reports\InvalidActionException;
use App\Exceptions\Reports\ChangeConcurrentlyEmailOrPhoneOrUsernameException;
use App\Exceptions\Reports\InvalidBalanceException;
use App\Exceptions\Reports\AccountNotActivedException;
use Validator;
use Mattermost;
use SystemNotification;
use App\Traits\NotificationTrait;
use Mail;
use App\Utils\TimeUtils;
use App\Utils\RankingUtils;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Nodebb;
use App\Jobs\SendSystemNotification;

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
            'visibleSettings', 'emailChanging', 'phoneChanging', 'phoneChangeCode', 'socialUser'
            ])
            ->leftJoin('user_rankings', 'user_rankings.user_id', 'users.id')
            ->where('users.id', $userId)
            ->select('users.id', 'users.email', 'users.username', 'users.email_verified', 'users.phone_number',
                'users.phone_verified', 'users.phone_country_code', 'users.level', 'users.avatar', 'users.description',
                'users.languages', 'users.last_time_active', 'users.user_type', 'users.is_vip', 'users.sex', 'users.dob',
                'users.phone_verify_code', 'user_rankings.ranking_id', 'user_rankings.total_exp', 'user_rankings.intro_step'
            )
            ->first();

        $user->chat_user = [
            'user_id' => $user->mattermostUser->user_id,
            'chat_user_id' => $user->mattermostUser->mattermost_user_id
        ];

        if (!empty($user->nodebbUser)) {
            $user->forum_user = [
                'user_id' => $user->nodebbUser->user_id,
                'forum_user_id' => $user->nodebbUser->nodebb_user_id
            ];
        }

        $user->is_social_user = false;
        if (!empty($user->socialUser)) {
            $user->is_social_user = true;
        }

        unset($user->nodebbUser);
        unset($user->mattermostUser);
        unset($user->socialUser);

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

        return $user;
    }

    public function getVisibleSettings($userId) {
        $user = UserSetting::select('id', 'visible_age', 'visible_gender', 'visible_following', 'online', 'cover')
            ->where('id', $userId)
            ->first();
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

        return 'Ok';
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
                'users.last_time_active', 'users.user_type', 'users.is_vip', 'user_rankings.ranking_id')
            ->where('users.username', $username)
            ->where('users.status', Consts::USER_ACTIVE)
            ->first();

        if (!$user) {
            return null;
        }

        $user->forum_user = [
            'user_id' => null,
            'forum_user_id' => null
        ];

        unset($user->nodebbUser);

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

        return $user->toArray();
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
            ->paginate(array_get($params, 'limit', Consts::DEFAULT_PER_PAGE));
    }

    public function getUserFollowers($params)
    {
        return UserFollowing::with(['fan'])
            ->where('following_id', $params['user_id'])
            ->where('is_following', Consts::TRUE)
            ->paginate(array_get($params, 'limit', Consts::DEFAULT_PER_PAGE));
    }

    public function addOrRemoveFollow($followingId, $isFollowing = Consts::TRUE)
    {
        $followerId = Auth::id();
        $follow = UserFollowing::firstOrNew(['user_id' => $followerId, 'following_id' => $followingId]);
        $follow->is_following = $isFollowing;
        $follow->save();

        if ($isFollowing) {
            $user = User::select('id', 'username', 'sex', 'avatar')
                ->where('id', $followerId)
                ->first();

            $notificationParams = [
                'user_id' => $followingId,
                'type' => Consts::NOTIFY_TYPE_NEW_FOLLOWER,
                'message' => Consts::NOTIFY_NEW_FOLLOW,
                'props' => [],
                'data' => ['user' => (object) ['id' => $followerId]]
            ];
            $this->fireNotification(Consts::NOTIFY_TYPE_OTHER, $notificationParams);

            CollectTaskingJob::dispatch($followerId, Tasking::FOLLOW_USER);
        }

        dispatch(new CalculateUserFollow($followerId, $followingId));

        return $follow;
    }

    public function changeEmailFromSetting($newEmail)
    {
        $user = Auth::user();

        $this->validateChangeEmailOrPhoneOrUsername($user);

        $code = Utils::generateRandomString(Consts::VERIFY_CODE_LENGTH, Consts::VERIFY_CODE_STRING);
        $changeEmailHistory = ChangeEmailHistory::create([
            'user_id' => $user->id,
            'old_email' => $user->email,
            'new_email' => strtolower($newEmail),
            'email_verification_code' => $code,
            'email_verification_code_created_at' => Carbon::now(),
        ]);
        \Mail::queue(new VerificationChangeMailQueue($changeEmailHistory, $user->username, Consts::DEFAULT_LOCALE));

        event(new UserUpdated($user->id));

        return $changeEmailHistory;
    }

    public function changeEmailFromSettingWithoutVerifiedAccount($newEmail)
    {
        $user = Auth::user();

        $this->validateChangeEmailOrPhoneOrUsername($user);

        $oldEmail = $user->email;
        $code = Utils::generateRandomString(Consts::VERIFY_CODE_LENGTH, Consts::VERIFY_CODE_STRING);

        $changeEmailHistory = ChangeEmailHistory::create([
            'user_id' => $user->id,
            'old_email' => $oldEmail,
            'new_email' => strtolower($newEmail),
            'email_verified' => Consts::TRUE,
            'without_verified_account' => Consts::TRUE
        ]);

        $user->email = $changeEmailHistory->new_email;
        $user->email_verification_code = $code;
        $user->save();

        $changeEmailHistory->delete();

        Mattermost::updateEmailUser($user->mattermostUser->mattermost_user_id, $oldEmail, $user->email);
        Nodebb::updateEmail($user->nodebbUser->nodebb_user_id, $user->email);
        \Mail::queue(new VerificationMailQueue($user, Consts::DEFAULT_LOCALE, $newEmail));

        PushAcountInfoHubSpotJob::dispatch($user);
        event(new UserUpdated($user->id));
        event(new EmailChanged($user->id, 'success'));

        return $changeEmailHistory;
    }

    public function resendCodeChangeEmail($email)
    {
        $user = Auth::user();
        $changeEmailHistory = ChangeEmailHistory::where('old_email', $user->email)
            ->where('new_email', $email)
            ->where('email_verified', Consts::FALSE)
            ->orderBy('id', 'desc')
            ->first();
        if (!$changeEmailHistory) {
            throw new ChangeEmailException('auth.verify.email_invalid');
        }
        $code = Utils::generateRandomString(Consts::VERIFY_CODE_LENGTH, Consts::VERIFY_CODE_STRING);
        $changeEmailHistory->email_verification_code = $code;
        $changeEmailHistory->email_verification_code_created_at = Carbon::now();
        $changeEmailHistory->save();

        \Mail::queue(new VerificationChangeMailQueue($changeEmailHistory, $user->username, Consts::DEFAULT_LOCALE));
        return $changeEmailHistory;
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

        Mattermost::updateEmailUser($user->mattermostUser->mattermost_user_id, $oldEmail, $newEmail);
        Nodebb::updateEmail($user->nodebbUser->nodebb_user_id, $newEmail);

        PushAcountInfoHubSpotJob::dispatch($user);
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

        event(new UserUpdated($userId));
        event(new UserProfileUpdated($userId));
        event(new UserSettingsUpdated($userId));

        return $settings;
    }

    public function updateProfile($userId, $params)
    {
        $dob = array_get($params, 'dob');
        $user = User::find($userId);
        $user->languages = array_get($params, 'languages', $user->languages);
        $user->description = array_get($params, 'description', $user->description);
        $user->avatar = array_get($params, 'avatar', $user->avatar);
        $user->sex = array_get($params, 'sex', $user->sex);
        if ($dob) {
            $user->dob = $dob;
        }

        if ($user->isDirty()) {
            $user->save();
        }

        event(new UserUpdated($userId));
        event(new UserProfileUpdated($userId));

        // save new info of user to cache.
        $userData = ChatUtils::getUserDataToCache($userId);
        ChatUtils::updateChannelMembers($userData);

        return $user;
    }

    public function cancelChangeEmail()
    {
        ChangeEmailHistory::where('user_id', Auth::id())
            ->where('email_verified', Consts::FALSE)
            ->whereNull('deleted_at')
            ->first()
            ->delete();
        event(new UserUpdated(Auth::id()));
        return 'ok';
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
        $report = UserReport::create([
            'user_id' => Auth::id(),
            'report_user_id' => $input['report_user_id'],
            'reason' => $input['reason']
        ]);

        return $report;
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

    public function changeUsernameFromSetting($newUsername)
    {
        $user = Auth::user();

        $this->validateChangeEmailOrPhoneOrUsername($user);

        $code = Utils::generateRandomString(Consts::VERIFY_CODE_LENGTH, Consts::VERIFY_CODE_STRING);
        $key = sprintf('%s%s%s%s', $newUsername, $user->username, Carbon::now(), $code);
        $changeUsernameHistory = ChangeUsernameHistory::create([
            'user_id' => $user->id,
            'old_username' => $user->username,
            'new_username' => $newUsername,
            'verification_code' => gamelancer_hash($key),
            'verification_code_created_at' => Carbon::now(),
        ]);

        $this->sendVerifyCodeChangeUsername($user, $changeUsernameHistory);
        event(new UserUpdated($user->id));

        return $changeUsernameHistory;
    }

    public function resendLinkChangeUsername($newUsername)
    {
        $user = Auth::user();
        $changeUsernameHistory = ChangeUsernameHistory::where('old_username', $user->username)
            ->where('new_username', $newUsername)
            ->where('verified', Consts::FALSE)
            ->orderBy('id', 'desc')
            ->first();

        if (!$changeUsernameHistory) {
            throw new ChangeUsernameException('auth.verify.username_invalid');
        }

        $code = Utils::generateRandomString(Consts::VERIFY_CODE_LENGTH, Consts::VERIFY_CODE_STRING);
        $key = sprintf('%s%s%s%s', $changeUsernameHistory->new_username, $changeUsernameHistory->old_username, Carbon::now(), $code);
        $changeUsernameHistory->verification_code = gamelancer_hash($key);
        $changeUsernameHistory->verification_code_created_at = Carbon::now();
        $changeUsernameHistory->save();

        $this->sendVerifyCodeChangeUsername($user, $changeUsernameHistory);
        return $changeUsernameHistory;
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

        Nodebb::updateUsername($user->nodebbUser->nodebb_user_id, $newUsername);

        PushAcountInfoHubSpotJob::dispatch($user);
        event(new UserUpdated($user->id));
        $data = [
            'user_id'        => $user->id,
            'old_username'   => $oldUsername,
            'username'       => $user->username
        ];
        event(new UsernameChanged($data));

        return $changeUsernameHistory;
    }

    public function cancelChangeUsername()
    {
        ChangeUsernameHistory::where('user_id', Auth::id())
            ->where('verified', Consts::FALSE)
            ->whereNull('deleted_at')
            ->first()->delete();
        event(new UserUpdated(Auth::id()));
        return 'ok';
    }

    public function changePhoneNumberFromSetting($newPhoneNumber, $newPhoneCountryCode)
    {
        $user = Auth::user();
        $this->validateChangeEmailOrPhoneOrUsername($user);

        $newPhoneNumber = PhoneUtils::makePhoneNumber($newPhoneNumber, $newPhoneCountryCode);

        $code = Utils::generateRandomString(Consts::VERIFY_CODE_LENGTH, Consts::VERIFY_CODE_STRING);
        $changePhoneNumberHistory = ChangePhoneNumberHistory::create([
            'user_id' => $user->id,
            'old_phone_number' => $user->phone_number,
            'new_phone_number' => $newPhoneNumber,
            'new_phone_country_code' => $newPhoneCountryCode
        ]);

        event(new UserUpdated($user->id));

        return $changePhoneNumberHistory;
    }

    public function changePhoneNumberFromSettingWithoutVerifiedAccount($newPhoneNumber, $newPhoneCountryCode)
    {
        $user = Auth::user();
        $this->validateChangeEmailOrPhoneOrUsername($user);

        $newPhoneNumber = PhoneUtils::makePhoneNumber($newPhoneNumber, $newPhoneCountryCode);

        $changePhoneNumberHistory = ChangePhoneNumberHistory::create([
            'user_id' => $user->id,
            'old_phone_number' => $user->phone_number,
            'new_phone_number' => $newPhoneNumber,
            'new_phone_country_code' => $newPhoneCountryCode,
            'phone_verified' => Consts::TRUE,
            'without_verified_account' => Consts::TRUE
        ]);

        $user->phone_number = $changePhoneNumberHistory->new_phone_number;
        $user->phone_country_code = $changePhoneNumberHistory->new_phone_country_code;
        $user->phone_verify_code = null;
        $user->phone_verify_created_at = null;
        $user->save();

        $changePhoneNumberHistory->delete();

        PushAcountInfoHubSpotJob::dispatch($user);
        event(new UserUpdated($user->id));
        event(new PhoneNumberChanged($user->id, 'success'));

        return $changePhoneNumberHistory;
    }

    public function resendCodeChangePhoneNumber($newPhoneNumber)
    {
        $user = Auth::user();
        $changePhoneNumberHistory = ChangePhoneNumberHistory::where('old_phone_number', $user->phone_number)
            ->where('new_phone_number', $newPhoneNumber)
            ->where('verified', Consts::FALSE)
            ->orderBy('id', 'desc')
            ->first();

        if (!$changePhoneNumberHistory) {
            throw new ChangePhoneNumberException('auth.verify.phone_number_invalid');
        }

        $allowPhoneNumber = PhoneUtils::allowSmsNotification((object) ['phone_country_code' => $changePhoneNumberHistory->new_phone_country_code]);
        if (!$allowPhoneNumber) {
            throw new ChangePhoneNumberException('exceptions.phone_not_supported');
        }

        $code = Utils::generateRandomString(Consts::VERIFY_CODE_LENGTH, Consts::VERIFY_CODE_STRING);
        $changePhoneNumberHistory->verification_code = $code;
        $changePhoneNumberHistory->verification_code_created_at = Carbon::now();
        $changePhoneNumberHistory->save();

        SendSmsNotificationJob::dispatch(
            $user,
            Consts::NOTIFY_SMS_PHONE_CODE,
            ['code' => $changePhoneNumberHistory->verification_code]
        );

        event(new UserUpdated($user->id));

        return $changePhoneNumberHistory;
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

        PushAcountInfoHubSpotJob::dispatch($user);
        event(new UserUpdated($user->id));

        event(new PhoneNumberChanged($user->id, 'success'));

        return $changePhoneNumberHistory;
    }

    public function cancelChangePhoneNumber()
    {
        ChangePhoneNumberHistory::where('user_id', Auth::id())
            ->where('verified', Consts::FALSE)
            ->whereNull('deleted_at')
            ->first()->delete();
        event(new UserUpdated(Auth::id()));
        return 'ok';
    }

    private function validateChangeEmailOrPhoneOrUsername ($user)
    {
        if ($user->newEmail || $user->newUsername || $user->newPhoneNumber) {
            throw new ChangeConcurrentlyEmailOrPhoneOrUsernameException();
        }

        return true;
    }

    public function savePhoneForUser($phoneNumber, $phoneCountryCode)
    {
        $user = Auth::user();

        if ($user->phone_number && $user->hasVerifiedPhone()) {
            throw new PhoneNumberVerifiedException();
        }

        $confirmationCode = Utils::generateRandomString(Consts::VERIFY_CODE_LENGTH, Consts::VERIFY_CODE_STRING);

        $user->phone_number = PhoneUtils::makePhoneNumber($phoneNumber, $phoneCountryCode);
        $user->phone_country_code = $phoneCountryCode;
        $user->phone_verify_code = $confirmationCode;
        $user->phone_verify_created_at = Carbon::now();
        $user->save();

        event(new UserUpdated($user->id));

        return $user;
    }

    public function checkPasswordValid($password)
    {
        if (Hash::check($password, Auth::user()->password)) {
            return 'ok';
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
        OtpUtils::initOtpCodeToCache($user, $confirmationCode);

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

    public function confirmOtpCode($confirmationCode)
    {
        $user = Auth::user();
        $result = OtpUtils::confirmOtpCode($user, $confirmationCode);
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
        $user = Auth::user();
        if (!$user->socialUser) {
            return [
                'is_verified' => true,
                'unlock_by' => Consts::SECURITY_UNLOCK_TYPE_PASSWORD
            ];
        }

        if ($user->email_verified) {
            $this->sendOtpCode(false);
            return [
                'is_verified' => true,
                'unlock_by' => Consts::SECURITY_UNLOCK_TYPE_EMAIL
            ];
        }

        if ($user->phone_verified && PhoneUtils::allowSmsNotification($user)) {
            $this->sendOtpCode(false);
            return [
                'is_verified' => true,
                'unlock_by' => Consts::SECURITY_UNLOCK_TYPE_PHONE
            ];
        }

        return [
            'is_verified' => false
        ];
    }
}
