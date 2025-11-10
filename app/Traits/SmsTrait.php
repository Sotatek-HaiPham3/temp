<?php

namespace App\Traits;

use App\Notifications\SendSmsNotification;
use App\Models\User;
use App\Consts;
use Auth;

trait SmsTrait {
    public static function sendVerifyCode(User $user)
    {
        $content = __('sms.verify_account', ['code' => $user->phone_verify_code]);

        static::sendSms($user, $content);
    }

    public static function sendChangePhoneNumber(User $user, $code)
    {
        $content = __('sms.change_phone_number', ['code' => $code]);

        static::sendSms($user, $content);
    }

    public static function sendResetPasswordCode(User $user, $token)
    {
        $link = reset_password_url($token, $user->email, $user->username);
        $content = __('sms.reset_password', ['link' => $link]);

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
}

