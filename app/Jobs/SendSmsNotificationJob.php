<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;
use App\Models\Bounty;
use App\Consts;
use Exception;
use SystemNotification;

class SendSmsNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $modelable;
    protected $type;
    protected $params;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($modelable, $type, $params = [])
    {
        $this->modelable = $modelable;
        $this->type = $type;
        $this->params = $params;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        switch ($this->type) {
            case Consts::NOTIFY_SMS_BOUNTY_RECEIVED:
                SystemNotification::sendBountyRequestReceived($this->modelable, $this->params);
                break;
            case Consts::NOTIFY_SMS_BOUNTY_ACCEPTED:
                SystemNotification::sendBountyRequestAccepted($this->modelable);
                break;
            case Consts::NOTIFY_SMS_SESSION_BOOKED:
                SystemNotification::sendSessionRequestHasBeenBooked($this->modelable);
                break;
            case Consts::NOTIFY_SMS_SESSION_ACCEPTED:
                SystemNotification::sendSessionRequestHasBeenAccepted($this->modelable);
                break;
            case Consts::NOTIFY_SMS_SESSION_STARTING:
                SystemNotification::sendSessionStartingSoon($this->modelable, $this->params);
                break;
            case Consts::NOTIFY_SMS_VERIFY_CODE:
                SystemNotification::sendVerifyCode($this->modelable);
                break;
            case Consts::NOTIFY_SMS_PHONE_CODE:
                SystemNotification::sendChangePhoneNumber($this->modelable, array_get($this->params, 'code'));
                break;
            case Consts::NOTIFY_SMS_PASSWORD_LINK:
                SystemNotification::sendResetPasswordLink($this->modelable, array_get($this->params, 'token'));
                break;
            case Consts::NOTIFY_SMS_PASSWORD_CODE:
                SystemNotification::sendResetPasswordCode($this->modelable, array_get($this->params, 'code'));
                break;
            case Consts::NOTIFY_SMS_USERNAME_CODE:
                SystemNotification::sendVerifyCodeChangeUsername($this->modelable, array_get($this->params, 'code'));
                break;
            case Consts::NOTIFY_SMS_CONFIRMATION_CODE:
                SystemNotification::sendConfirmationCode($this->modelable, array_get($this->params, 'code'));
                break;
            case Consts::NOTIFY_SMS_APP_VALIDATE_CODE:
                SystemNotification::sendValidateCode($this->modelable, array_get($this->params, 'code'));
                break;
            case Consts::NOTIFY_SMS_APP_LOGIN_CODE:
                SystemNotification::sendLoginCode($this->modelable, array_get($this->params, 'code'));
                break;
            case Consts::NOTIFY_SMS_AUTHORIZATION_CODE:
                SystemNotification::sendAuthorizationCode($this->modelable, array_get($this->params, 'code'));
                break;
            default:
                // To do handle something else
                break;
        }
    }
}
