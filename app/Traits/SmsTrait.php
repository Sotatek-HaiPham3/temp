<?php

namespace App\Traits;

use Twilio\Rest\Client;
use App\Notifications\SendSmsNotification;
use App\Models\User;
use App\Models\SmsSetting;
use App\Consts;
use Auth;
use NotificationChannels\Twilio\TwilioSmsMessage;
use NotificationChannels\Twilio\Twilio;
use Illuminate\Support\Facades\Cache;

trait SmsTrait {
    public static function sendVerifyCode(User $user)
    {
        $content = __('sms.verify_account', ['code' => $user->phone_verify_code]);

        static::sendSms($user, $content);
    }

    public static function sendChangePhoneNumber($phoneNumber, $code)
    {
        $content = __('sms.change_phone_number', ['code' => $code]);
        static::sendUnauthenticateSms($phoneNumber, $content);
    }

    public static function sendResetPasswordLink(User $user, $token)
    {
        $link = reset_password_url($token, $user->email, $user->username);
        $content = __('sms.reset_password', ['link' => $link]);

        static::sendSms($user, $content);
    }

    public static function sendResetPasswordCode(User $user, $code)
    {
        $content = __('sms.reset_password_code', ['code' => $code]);

        static::sendSms($user, $content);
    }

    public static function sendVerifyCodeChangeUsername(User $user, $code)
    {
        $link = verify_change_username_url($code);
        $content = __('sms.change_username', ['link' => $link]);

        static::sendSms($user, $content);
    }

    public static function sendConfirmationCode(User $user, $code)
    {
        $content = __('sms.social_confirmation', ['code' => $code]);

        static::sendSms($user, $content);
    }

    public static function sendAuthorizationCode(User $user, $code)
    {
        $content = __('sms.authorization_confirmation', ['code' => $code]);

        static::sendSms($user, $content);
    }

    public static function sendBountyRequestReceived($bounty, $params)
    {
        $user = static::getUserForNotifySms($bounty->user_id);

        list($gamelancerId) = $params;
        $gamelancerName = static::getUserForNotifySms($gamelancerId)->username;
        $link = bounty_detail_link($user->username, $bounty->slug);

        $contents = [
            'title' => __('sms.bounty_request_received.title'),
            'bounty_name' => __('sms.bounty_request_received.bounty_name', ['bountyName' => $bounty->title]),
            'gamelancer_name' => __('sms.bounty_request_received.gamelancer_name', ['gamelancerName' => $gamelancerName]),
            'bounty_link' => __('sms.bounty_request_received.bounty_link', ['link' => $link])
        ];

        static::sendSms($user, $contents);
    }

    public static function sendBountyRequestAccepted($bounty)
    {
        $user = static::getUserForNotifySms($bounty->user_id);
        $link = chat_link($user->username);

        $contents = [
            'title' => __('sms.bounty_request_accepted.title'),
            'bounty_name' => __('sms.bounty_request_accepted.bounty_name', ['bountyName' => $bounty->title]),
            'user_name' => __('sms.bounty_request_accepted.user_name', ['userName' => $user->username]),
            'make_sure' => __('sms.bounty_request_accepted.make_sure'),
            'chat_link' => __('sms.bounty_request_accepted.chat_link', ['chatLink' => $link])
        ];

        $gamelancerId = $bounty->claimBountyRequest->gamelancer_id;

        static::sendSms(static::getUserForNotifySms($gamelancerId), $contents);
    }

    public static function sendSessionRequestHasBeenBooked($session)
    {
        $user = static::getUserForNotifySms($session->gamelancer_id);

        $gamelancerName = static::getUserForNotifySms($session->claimer_id)->username;
        $link = chat_link($gamelancerName);

        $contents = [
            'title' => __('sms.session_request_has_been_booked.title'),
            'session_name' => __('sms.session_request_has_been_booked.session_name', ['sessionName' => static::getGameTitle($session)]),
            'gamelancer_name' => __('sms.session_request_has_been_booked.gamelancer_name', ['gamelancerName' => $gamelancerName]),
            'make_sure' => __('sms.session_request_has_been_booked.make_sure'),
            'chat_link' => __('sms.session_request_has_been_booked.chat_link', ['chatLink' => $link])
        ];

        static::sendSms($user, $contents);
    }

    public static function sendSessionRequestHasBeenAccepted($session)
    {
        $user = static::getUserForNotifySms($session->claimer_id);

        $userName = static::getUserForNotifySms($session->gamelancer_id)->username;
        $link = chat_link($userName);

        $contents = [
            'title' => __('sms.session_request_has_been_accepted.title'),
            'session_name' => __('sms.session_request_has_been_accepted.session_name', ['sessionName' => static::getGameTitle($session)]),
            'user_name' => __('sms.session_request_has_been_accepted.user_name', ['userName' => $userName]),
            'chat_link' => __('sms.session_request_has_been_accepted.chat_link', ['chatLink' => $link])
        ];
        static::sendSms($user, $contents);
    }

    public static function sendSessionStartingSoon($session, $params)
    {
        list($userId, $partnerUserId) = $params;
        static::executeSendSessionStartingSoon($userId, $partnerUserId, $session);
    }

    private static function executeSendSessionStartingSoon($userId, $partnerUserId, $session)
    {
        $user = static::getUserForNotifySms($userId);

        $userName = static::getUserForNotifySms($partnerUserId)->username;
        $link = chat_link($userName);

        $contents = [
            'title' => __('sms.session_starting_soon.title'),
            'session_name' => __('sms.session_starting_soon.session_name', ['sessionName' => static::getGameTitle($session)]),
            'session_start_time' => __('sms.session_starting_soon.session_start_time', ['startTime' => Consts::SESSION_CHECK_READY_STARTING_TIME]),
            'user_name' => __('sms.session_starting_soon.user_name', ['userName' => $userName]),
            'make_sure' => __('sms.session_starting_soon.make_sure'),
            'chat_link' => __('sms.session_starting_soon.chat_link', ['chatLink' => $link])
        ];

        static::sendSms($user, $contents);
    }

    private static function sendSms(User $user, $contents)
    {
        if (!static::canPerformSendSms($user->phone_number)) {
            return;
        }

        $user->notify(new SendSmsNotification(static::arrayToString($contents)));
    }

    private static function getUserForNotifySms($id)
    {
        return User::find($id);
    }

    private static function getGameTitle($session)
    {
        return $session->gameProfile->game->title;
    }

    private static function arrayToString($contents)
    {
        if (is_array($contents)) {
            return implode('', $contents);
        }

        return $contents;
    }

    public static function sendValidateCode($phoneNumber, $code)
    {
        $content = __('sms.validate_code', ['code' => $code]);
        static::sendUnauthenticateSms($phoneNumber, $content);
    }

    public static function sendLoginCode($phoneNumber, $code)
    {
        $content = __('sms.login_code', ['code' => $code]);
        static::sendUnauthenticateSms($phoneNumber, $content);
    }

    private static function sendUnauthenticateSms($phoneNumber, $content)
    {
        if (!static::canPerformSendSms($phoneNumber)) {
            return;
        }

        $client = new Client(env('TWILIO_ACCOUNT_SID'), env('TWILIO_AUTH_TOKEN'));
        $client->messages->create(
            $phoneNumber,
            [
                'from' => env('TWILIO_FROM'),
                'body' => $content
            ]
        );
    }

    private static function canPerformSendSms($phoneNumber)
    {
        $client = $client = new Client(env('TWILIO_ACCOUNT_SID'), env('TWILIO_AUTH_TOKEN'));

        $smsSetting = SmsSetting::getData();

        /**
         * Lookup our phone number...
         */
        $number = $client->lookups->phoneNumbers($phoneNumber)->fetch();
        
        /**
         * USA and Canada are fine.
         */
        if (in_array($number->countryCode, $smsSetting->white_list)) {
            logger('===========Country white listed===========: ', [$number->countryCode]);

            return true;
        }

        $priceKey = static::priceKey($number->countryCode);
        $rateKey = static::rateKey($number->countryCode);

        /**
         * CHECK OUR CACHE FOR RECENT PRICING LOOKUP HERE
         */
        $price = Cache::get($priceKey) ?? false;

        if (!$price) {
            /**
             * Fetch the country pricing for that country
             * if we don't have it cached
             */
            $country = $client->pricing->v1->messaging
                           ->countries($number->countryCode)
                           ->fetch();

            /**
             * No prices returned.
             */
            if (empty($country->outboundSmsPrices[0]['prices']) || !is_array($country->outboundSmsPrices[0]['prices'])) {
                return false;
            }

            $price = $country->outboundSmsPrices[0]['prices'][0]['current_price'];

            Cache::add($priceKey, $price, 86400);
        }

        /**
         * BEYOND OUR MAX PRICE, NO TEXT ALLOWED.
         * SAVE THE PRICE AND RATE LIMIT.
         */
        if ($price >= $smsSetting->max_price && !in_array($number->countryCode, $smsSetting->rate_list)) {
            logger('===========Maximum price exceeded===========: ', [$number->countryCode, $price]);

            return false;
        }

        /**
         * PRICE FOUND ABOVE OUR RATE LIMIT..
         */
        $rate = Cache::get($rateKey) ?? 0;
        if (intval($rate) >= $smsSetting->rate_limit) {
            /**
             * Too many requests in time period.
             * Cooldown refreshed and incremented.
             */
            logger('===========Rate limit exceeded===========: ', [$number->countryCode, $rate, $price]);

            return false;
        }

        if ($price >= $smsSetting->rate_limit_price) {
            $rate++;
            Cache::put($rateKey, $rate, $smsSetting->rate_limit_ttl);
        }

        logger('===========Country===========: ', [$number->countryCode]);
        logger('===========Price===========: ', [$price]);
        logger('===========Rate limit===========: ', [$rate]);

        return true;
    }

    private static function priceKey($countryCode)
    {
        return "twilio.price.{$countryCode}";
    }

    private static function rateKey($countryCode)
    {
        return "twilio.rate-limit.{$countryCode}";
    }
}
