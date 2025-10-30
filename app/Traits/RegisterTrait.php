<?php

namespace App\Traits;

use App\Mails\RegisterVerificationMailQueue;
use App\Models\User;
use App\Models\SocialUser;
use App\Utils;
use App\Consts;
use DB;
use Exception;
use Mail;
use App\Mails\VerificationMailQueue;
use App\Jobs\CreateMattermostUserEndpoint;
use App\Jobs\SendSmsNotificationJob;
use Illuminate\Auth\Events\Registered;
use Carbon\Carbon;
use App\Http\Services\MattermostService;
use App\Exceptions\Reports\MattermostException;
use App\Exceptions\Reports\InvalidCodeException;
use App\PhoneUtils;
use App\Utils\OtpUtils;
use Aws;

trait RegisterTrait {

    public function doRegister($input, $options = [])
    {
        $user = null;
        DB::beginTransaction();
        try {
            $user = $this->createUser($input, $options);

            DB::commit();

            event(new Registered($user));

            $data = [
                'when' => 'signup',
                'data' => [
                    'user_id' => $user->id,
                    'email' => $user->email
                ]
            ];
            Aws::performFirehosePut($data, $user);

            CreateMattermostUserEndpoint::dispatchNow($user);

            return $user;
        } catch (Exception $ex) {
            DB::rollback();
            logger()->error($ex);

            if ($ex instanceof MattermostException) {
                $cloneUser = clone $user;
                $username = sprintf('%s_%s', $cloneUser->username, Utils::currentMilliseconds());
                $cloneUser->username = $username;
                logger('==========Register mattermost user with currentMilliseconds============', [$cloneUser]);

                CreateMattermostUserEndpoint::dispatch($cloneUser)->onQueue(Consts::CREATE_MATTERMOST_USER_ENDPOINT_QUEUE);

                return $user;
            }

            throw $ex;
        }
    }

    private function createUser($data, $options = [])
    {
        if (empty(array_get($data, 'email'))) {
            $data['email'] = Utils::generateAutoEmail();
        }

        $hasPhoneNumber = !empty(array_get($data, 'phone_number'));

        $user = User::firstOrNew([
            'email' => strtolower(array_get($data, 'email'))
        ]);

        $password = array_get($data, 'password');
        $user->password = $password ? bcrypt($password) : null;
        $user->dob = array_get($data, 'dob');
        $user->username = array_get($data, 'username');
        $user->status = Consts::USER_ACTIVE;

        $user->languages = [Consts::DEFAULT_LOCALE];

        $user->phone_country_code = $hasPhoneNumber ? PhoneUtils::getCountryCodeByFullPhoneNumber(array_get($data, 'phone_number')) : null;
        $user->phone_number = $hasPhoneNumber ? array_get($data, 'phone_number') : null;

        if (!empty(array_get($data, 'languages'))) {
            $user->languages = array_get($data, 'languages');
        }

        if ($hasPhoneNumber && !empty(array_get($data, 'validate_code'))) {
            $user->phone_verified = OtpUtils::confirmValidateCode($user->phone_number, array_get($data, 'validate_code')) ? Consts::TRUE : Consts::FALSE;
        }

        if (!$hasPhoneNumber && !empty(array_get($data, 'validate_code'))) {
            $user->email_verified = OtpUtils::confirmEmailValidateCodeToCache($data['email'], array_get($data, 'validate_code')) ? Consts::TRUE : Consts::FALSE;
        }

        $user->save();

        DB::table('user_settings')->insert(['id' => $user->id]);
        DB::table('user_statistics')->insert(['user_id' => $user->id]);

        // create social_user if login with social network
        if (!empty($options['provider'])) {
            $this->createSocialUser($user, $options['provider'], $options['provider_user']);
        }

        return $user;
    }

    private function createSocialUser($user, $provider, $providerUser)
    {
        $socialUser = new SocialUser;
        $socialUser->provider = $provider;
        $socialUser->provider_id= $providerUser->id;
        $socialUser->email = !empty($providerUser->email) ? strtolower($providerUser->email) : null;
        $socialUser->phone_number = !empty($providerUser->phone) ? $providerUser->phone : null;

        $user->socialUser()->save($socialUser);
    }

    public function sendValidateCode($params)
    {
        $code = Utils::generateRandomString(Consts::VERIFY_CODE_LENGTH, Consts::VERIFY_CODE_STRING);
        OtpUtils::initValidateCodeToCache($params['phone_number'], $code);

        SendSmsNotificationJob::dispatch(
            $params['phone_number'],
            Consts::NOTIFY_SMS_APP_VALIDATE_CODE,
            ['code' => $code]
        );
        return true;
    }

    public function confirmValidateCode($phoneNumber, $code)
    {
        if (OtpUtils::confirmValidateCode($phoneNumber, $code, false)) {
            return true;
        }
        throw new InvalidCodeException();
    }

    public function sendEmailValidateCode($params)
    {
        $code = Utils::generateRandomString(Consts::VERIFY_CODE_LENGTH, Consts::VERIFY_CODE_STRING);
        OtpUtils::initEmailValidateCodeToCache($params['email'], $code);

        Mail::queue(new RegisterVerificationMailQueue($params['email'], Consts::DEFAULT_LOCALE, $code));
        return true;
    }

    public function confirmEmailValidateCode($email, $code)
    {
        if (OtpUtils::confirmEmailValidateCodeToCache($email, $code, false)) {
            return true;
        }
        throw new InvalidCodeException();
    }
}
