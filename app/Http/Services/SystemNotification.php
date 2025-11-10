<?php

namespace App\Http\Services;

use App\Events\SystemNotification as SystemNotificationEvent;
use App\Models\SystemNotification as SystemNotificationModel;
use App\Models\UserSetting;
use App\Models\User;
use App\Http\Services\FirebaseService;
use App\Consts;
use Mail;
use Carbon\Carbon;
use Illuminate\Contracts\Mail\Mailable;
use App\Jobs\ExceptionEmailJob;
use App\Traits\SmsTrait;
use App\PhoneUtils;

class SystemNotification {

    use SmsTrait;

    public static function notifyNewMessage($userId, $type, $message, $messageProps = [], $data = [])
    {
        $notification = static::create($userId, $type, $message, $messageProps, $data, SystemNotificationModel::TYPE_NEW_MESSAGE);
        return $notification;
    }

    public static function notifyFavoriteActivity($userId, $type, $message, $messageProps = [], $data = [])
    {
        $notification = static::create($userId, $type, $message, $messageProps, $data, SystemNotificationModel::TYPE_FAVORITE);
        return $notification;
    }

    public static function notifyMarketing($userId, $type, $message, $messageProps = [], $data = [])
    {
        $notification = static::create($userId, $type, $message, $messageProps, $data, SystemNotificationModel::TYPE_MARKETING);
        return $notification;
    }

    public static function notifyBountyActivity($userId, $type, $message, $messageProps = [], $data = [])
    {
        $notification = static::create($userId, $type, $message, $messageProps, $data, SystemNotificationModel::TYPE_BOUNTY);
        return $notification;
    }

    public static function notifySessionActivity($userId, $type, $message, $messageProps = [], $data = [])
    {
        $notification = static::create($userId, $type, $message, $messageProps, $data, SystemNotificationModel::TYPE_SESSION);
        return $notification;
    }

    public static function notifyVideoActivity($userId, $type, $message, $messageProps = [], $data = [])
    {
        $notification = static::create($userId, $type, $message, $messageProps, $data, SystemNotificationModel::TYPE_VIDEO);
        return $notification;
    }

    public static function notifyTasking($userId, $type, $message, $messageProps = [], $data = [])
    {
        $notification = static::create($userId, $type, $message, $messageProps, $data, SystemNotificationModel::TYPE_TASKING);
        return $notification;
    }

    public static function notifyOther($userId, $type, $message, $messageProps = [], $data = [])
    {
        $notification = static::create($userId, $type, $message, $messageProps, $data);
        return $notification;
    }

    public static function getNotifiesByUserId($userId, $params)
    {
        $filterType = array_get($params, 'filter', null);
        $listType = static::getListNotifyType($filterType);

        $paginator = SystemNotificationModel::where('receiver_id', $userId)
            ->select('id', 'receiver_id as user_id', 'type', 'message_key', 'message_props', 'data', 'read_at', 'view_at', 'created_at')
            ->where(function ($query) {
                $query->whereNull('read_at')
                    ->orWhere('created_at', '>', Carbon::now()->subDays(Consts::LIMIT_DAYS_GET_NOTIFY));
            })
            ->when(!empty($listType), function ($query) use ($listType) {
                $query->whereIn('type', $listType);
            })
            ->orderBy('created_at', 'desc')
            ->paginate(array_get($params, 'limit', Consts::DEFAULT_PER_PAGE));

        $userIds = $paginator->getCollection()->pluck('data.user.id');
        $users = User::withoutAppends()->whereIn('id', $userIds)->get()->mapWithKeys(function ($user) {
            return [$user['id'] => $user];
        })->all();

        $paginator->getCollection()->transform(function ($notify) use ($users) {
            if (empty($notify->data->user)) {
                return $notify;
            }

            $userId = $notify->data->user->id;
            if (!empty($userId) && !empty($users[$userId])) {
                $user = $users[$userId];

                $cloneData = (object) cloneDeep($notify->data);
                $cloneData->user = $user;

                $cloneProps = $notify->message_props ? (object) cloneDeep($notify->message_props) : (object) [];
                $cloneProps->username = $user->username;

                $notify->data = cloneDeep($cloneData);
                $notify->message_props = cloneDeep($cloneProps);
            }
            return $notify;
        });

        return $paginator;
    }

    public static function totalUnview($userId)
    {
        return SystemNotificationModel::where('receiver_id', $userId)
            ->whereNull('view_at')
            ->count();
    }

    public static function markAsRead($userId, $notifyIds = [])
    {
        return static::markAsReadNotification($userId, [
            'notify_ids' => $notifyIds
        ]);
    }

    public static function markAsView($userId)
    {
        return SystemNotificationModel::where('receiver_id', $userId)
            ->whereNull('view_at')
            ->update([ 'view_at' => now() ]);
    }

    public static function markAsReadByType($userId, $type, $notifyIds = [])
    {
        return static::markAsReadNotification($userId, [
            'type' => $type,
            'notify_ids' => $notifyIds
        ]);
    }

    private static function markAsReadNotification($userId, $params)
    {
        return SystemNotificationModel::where('receiver_id', $userId)
            ->when(!empty($params['type']), function ($query) use ($params) {
                $query->where('type', $params['type']);
            })
            ->when(!empty($params['notify_ids']), function ($query) use ($params) {
                $query->whereIn('id', $params['notify_ids']);
            })
            ->whereNull('read_at')
            ->update([ 'read_at' => now() ]);
    }

    public static function deleteNotification($userId, $filterType)
    {
        $listType = static::getListNotifyType($filterType);
        return SystemNotificationModel::where('receiver_id', $userId)
            ->when(!empty($listType), function ($query) use ($listType) {
                $query->whereIn('type', $listType);
            })
            ->delete();
    }

    private static function getListNotifyType($filterType)
    {
        switch ($filterType) {
            case Consts::NOTIFY_FILTER_BALANCES:
                $listType =  [
                    Consts::NOTIFY_TYPE_WALLET_COINS,
                    Consts::NOTIFY_TYPE_WALLET_USD
                ];
                break;
            case Consts::NOTIFY_FILTER_BOUNTY:
                $listType =  [
                    Consts::NOTIFY_TYPE_BOUNTY,
                    Consts::NOTIFY_TYPE_BOUNTY_WALLET_COINS,
                    Consts::NOTIFY_TYPE_BOUNTY_WALLET_REWARDS,
                    Consts::NOTIFY_TYPE_BOUNTY_REVIEW
                ];
                break;
            case Consts::NOTIFY_FILTER_SESSION:
                $listType =  [
                    Consts::NOTIFY_TYPE_SESSION,
                    Consts::NOTIFY_TYPE_SESSION_WALLET_COINS,
                    Consts::NOTIFY_TYPE_SESSION_WALLET_REWARDS,
                    Consts::NOTIFY_TYPE_SESSION_REVIEW
                ];
                break;
            case Consts::NOTIFY_FILTER_FAVOURITE:
                $listType =  [
                    Consts::NOTIFY_TYPE_FAVORITE
                ];
                break;
            case Consts::NOTIFY_FILTER_OTHER:
                $listType =  [
                    Consts::NOTIFY_TYPE_NEW_MESSAGE,
                    Consts::NOTIFY_TYPE_MARKETING,
                    Consts::NOTIFY_TYPE_SESSION_ONLINE,
                    Consts::NOTIFY_TYPE_CONFIRM_GAMELANCER,
                    Consts::NOTIFY_TYPE_NEW_FOLLOWER,
                    Consts::NOTIFY_TYPE_TIP
                ];
                break;
            default:
                $listType = [];
        }
        return $listType;
    }

    private static function create($userId, $type, $message, $messageProps, $data = [], $typeActivity = null)
    {
        $mailable = null;
        if (!empty($data['mailable'])) {
            $mailable = $data['mailable'];
            unset($data['mailable']);
        }

        $smsable = null;
        if (!empty($data['smsable'])) {
            $smsable = $data['smsable'];
            unset($data['smsable']);
        }

        $notification = SystemNotificationModel::create([
            'type'          => $type,
            'receiver_id'   => $userId,
            'message_key'   => $message,
            'message_props' => $messageProps,
            'data'          => $data
        ]);

        static::pushNotification($notification, $typeActivity);

        static::fireNotificationEvent($userId, $notification);

        if ($typeActivity) {
            static::notifyActivity($notification, $typeActivity, $mailable, $smsable);
        }

        return $notification;
    }

    private static function fireNotificationEvent($userId, $notification)
    {
        if (!empty($notification->data->user) && !empty($notification->data->user->id)) {
            $user = User::withoutAppends()
                ->select('id', 'sex', 'username', 'avatar')
                ->where('id', $notification->data->user->id)
                ->first();

            $cloneData = (object) cloneDeep($notification->data);
            $cloneData->user = $user;

            $cloneProps = $notification->message_props ? (object) cloneDeep($notification->message_props) : (object) [];
            $cloneProps->username = $user->username;

            $notification->data = cloneDeep($cloneData);
            $notification->message_props = cloneDeep($cloneProps);
        }
        event(new SystemNotificationEvent($userId, cloneDeep($notification)));
    }

    private static function pushNotification($notification, $title)
    {
        $data = (array) $notification->data;
        $data['type'] = $notification->type;

        $params = [
            'title' => $title ? __('notification.' . $title) : __(Consts::OTHER_NOTIFY_APP),
            'body' => static::getBodyOfNotification($notification),
            'data' => $data
        ];

        (new FirebaseService())->pushNotifcation($notification->receiver_id, $params);
    }

    private static function getBodyOfNotification($notification)
    {
        $data = (array) $notification->message_props;

        $shouldReplace = (array_key_exists('rewards', $data) && empty($data['rewards'])) ||
            (array_key_exists('coins', $data) && empty($data['coins']));

        $messageKey = $shouldReplace ? "{$notification->message_key}_0" : $notification->message_key;

        return __($messageKey, $data);
    }

    private static function notifyActivity($notification, $typeActivity, $mailable, $smsable = null)
    {
        $user = static::getUser($notification->receiver_id);

        $allowSendingSms = !empty($smsable) && $user->hasVerifiedPhone() && PhoneUtils::allowSmsNotification($user);

        logger()->info('=========notifyActivity:: ', [
            'user' => $user,
            'is_send' => $allowSendingSms
        ]);

        if ($allowSendingSms) {
            static::notifySms($notification, $typeActivity, $smsable);
        }

        if ($user->hasVerifiedEmail()) {
            static::notifyEmail($notification, $typeActivity, $mailable);
        }
    }

    private static function notifyEmail($notification, $typeActivity, Mailable $mailable = null)
    {
        if (!$mailable) {
            static::logSystem('mailable invalid');
            return;
        }

        $setting = static::getUserSetting($notification->receiver_id);

        switch ($typeActivity) {
            case SystemNotificationModel::TYPE_NEW_MESSAGE:
                if (!empty($setting['message_email'])) {
                    Mail::queue($mailable);
                }
                break;
            case SystemNotificationModel::TYPE_FAVORITE:
                if (!empty($setting['favourite_email'])) {
                    Mail::queue($mailable);
                }
                break;
            case SystemNotificationModel::TYPE_MARKETING:
                if (!empty($setting['marketing_email'])) {
                    Mail::queue($mailable);
                }
                break;
            case SystemNotificationModel::TYPE_BOUNTY:
                if (!empty($setting['bounty_email'])) {
                    Mail::queue($mailable);
                }
                break;
            case SystemNotificationModel::TYPE_SESSION:
                if (!empty($setting['session_email'])) {
                    Mail::queue($mailable);
                }
                break;
            default:
                static::logSystem('system notification type invalid.');
                break;
        }
    }

    private static function notifySms($notification, $typeActivity, $smsable)
    {
        $setting = static::getUserSetting($notification->receiver_id);

        switch ($typeActivity) {
            case SystemNotificationModel::TYPE_MARKETING:
                if (!empty($setting['marketing_phone_number'])) {
                    dispatch($smsable);
                }
                break;
            case SystemNotificationModel::TYPE_BOUNTY:
                if (!empty($setting['bounty_phone_number'])) {
                    dispatch($smsable);
                }
                break;
            case SystemNotificationModel::TYPE_SESSION:
                if (!empty($setting['session_phone_number'])) {
                    dispatch($smsable);
                }
                break;
            default:
                static::logSystem('system notification type invalid.');
                break;
        }
    }

    private static function getUserSetting($userId)
    {
        $setting = UserSetting::find($userId);
        if (!$setting) {
            static::logSystem('user setting invalid.');

            return [];
        }

        return $setting;
    }

    private static function getUser($userId)
    {
        return User::where('id', $userId)->first();
    }

    private static function logSystem($message)
    {
        logger()->warning('================SystemNotification:: ', ['message' => $message]);
    }

    public static function sendExceptionEmail($content)
    {
        ExceptionEmailJob::dispatch($content)->onQueue(Consts::QUEUE_EXCEPTION_EMAIL);
    }
}
