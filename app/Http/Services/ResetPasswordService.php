<?php

namespace App\Http\Services;

use Illuminate\Foundation\Auth\ResetsPasswords;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Auth\Passwords\PasswordBroker;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\Request;
use Jenssegers\Agent\Facades\Agent;
use Mail;
use App\Exceptions\Reports\ResetPasswordUserException;
use App\Exceptions\Reports\PhoneNumberNotSupportedException;
use App\Exceptions\Reports\InvalidDataException;
use App\Exceptions\Reports\InvalidCodeException;
use App\Models\User;
use App\PhoneUtils;
use App\Utils;
use App\Utils\OtpUtils;
use App\Consts;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Jobs\SendSmsNotificationJob;
use App\Mails\ResetPasswordCodeMail;
use App\Mails\ChangePasswordMail;

class ResetPasswordService extends BaseService {

    use ResetsPasswords;

    protected $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    public function sendResetPasswordCode($params)
    {
        $user = $this->userService->getUserWithEmailOrPhone($params);
        if (!$user) {
            throw new InvalidDataException('exceptions.not_existed.user');
        }

        $phone = array_get($params, 'phone_number');
        if ($phone && !PhoneUtils::allowSmsNotification($user)) {
            throw new PhoneNumberNotSupportedException();
        }

        $code = Utils::generateRandomString(Consts::VERIFY_CODE_LENGTH, Consts::VERIFY_CODE_STRING);
        OtpUtils::initResetPwCodeToCache($user->id, $code);

        if ($phone) {
            SendSmsNotificationJob::dispatch($user, Consts::NOTIFY_SMS_PASSWORD_CODE, ['code' => $code]);
        } else {
            Mail::queue(new ResetPasswordCodeMail($user, $code));
        }

        return [];
    }

    public function confirmResetPwCode($params)
    {
        $user = $this->userService->getUserWithEmailOrPhone($params);
        if (!$user) {
            throw new InvalidDataException('exceptions.not_existed.user');
        }

        if (!OtpUtils::confirmResetPwCode($user->id, array_get($params, 'code'), false)) {
            throw new InvalidCodeException();
        }
        return [];
    }

    public function executeResetPassword($params)
    {
        $user = $this->userService->getUserWithEmailOrPhone($params);
        if (!$user) {
            throw new InvalidDataException('exceptions.not_existed.user');
        }

        if (!OtpUtils::confirmResetPwCode($user->id, array_get($params, 'code'))) {
            throw new InvalidCodeException();
        }

        $data = $this->resetPassword($user, $params['password']);

        Mail::queue(new ChangePasswordMail($user));
        return $data;
    }

    protected function resetPassword($user, $password)
    {
        $user->password = Hash::make($password);

        $user->setRememberToken(Str::random(60));

        $user->save();

        event(new PasswordReset($user));
    }
}
