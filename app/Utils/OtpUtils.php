<?php

namespace App\Utils;

use Cache;
use App\Consts;
use App\Utils;
use App\Utils\BigNumber;
use App\Utils\CurrencyExchange;

class OtpUtils
{
    const EXPRIED_TIME = 5 * 60; //seconds
    const OTP_CODE_KEY = 'OTPCode_via_user_';
    const VALIDATE_CODE_KEY = 'validate_code_via_phonenumber_';
    const VALIDATE_EMAIL_CODE_KEY = 'validate_code_via_email_';
    const LOGIN_CODE_KEY = 'login_code_for_phonenumber_';
    const RESET_PW_CODE_KEY = 'reset_password_code_';
    const AUTHORIZATION_CODE_KEY = 'authorization_code_';
    const EMAIL_OTP_CODE_KEY = 'OTPCode_via_email_';
    const PHONE_OTP_CODE_KEY = 'OTPCode_via_phone_';

    public static function initOtpCodeToCache($userId, $otpCode)
    {
        static::saveToCache($userId, $otpCode, static::OTP_CODE_KEY);
    }

    public static function initEmailOtpCodeToCache($userId, $otpCode)
    {
        static::saveToCache($userId, $otpCode, static::EMAIL_OTP_CODE_KEY);
    }

    public static function initPhoneOtpCodeToCache($userId, $otpCode)
    {
        static::saveToCache($userId, $otpCode, static::PHONE_OTP_CODE_KEY);
    }

    public static function initValidateCodeToCache($phonenumber, $validateCode)
    {
        static::saveToCache($phonenumber, $validateCode, static::VALIDATE_CODE_KEY);
    }

    public static function initEmailValidateCodeToCache($email, $validateCode)
    {
        static::saveToCache($email, $validateCode, static::VALIDATE_EMAIL_CODE_KEY);
    }

    public static function initLoginCodeToCache($phonenumber, $loginCode)
    {
        static::saveToCache($phonenumber, $loginCode, static::LOGIN_CODE_KEY);
    }

    public static function initResetPwCodeToCache($phonenumber, $restPwCode)
    {
        static::saveToCache($phonenumber, $restPwCode, static::RESET_PW_CODE_KEY);
    }

    public static function initAuthorizationCodeToCache($userId, $code)
    {
        static::saveToCache($userId, $code, static::AUTHORIZATION_CODE_KEY);
    }

    public static function confirmOtpCode($id, $code, $delete = true)
    {
        return static::checkCodeInCache($id, $code, static::OTP_CODE_KEY, $delete);
    }

    public static function confirmEmailOtpCode($id, $code, $delete = true)
    {
        return static::checkCodeInCache($id, $code, static::EMAIL_OTP_CODE_KEY, $delete);
    }

    public static function confirmPhoneOtpCode($id, $code, $delete = true)
    {
        return static::checkCodeInCache($id, $code, static::PHONE_OTP_CODE_KEY, $delete);
    }

    public static function confirmValidateCode($phonenumber, $code, $delete = true)
    {
        return static::checkCodeInCache($phonenumber, $code, static::VALIDATE_CODE_KEY, $delete);
    }

    public static function confirmEmailValidateCodeToCache($email, $code, $delete = true)
    {
        return static::checkCodeInCache($email, $code, static::VALIDATE_EMAIL_CODE_KEY, $delete);
    }

    public static function confirmLoginCode($phonenumber, $code)
    {
        return static::checkCodeInCache($phonenumber, $code, static::LOGIN_CODE_KEY);
    }

    public static function confirmResetPwCode($id, $code, $delete = true)
    {
        return static::checkCodeInCache($id, $code, static::RESET_PW_CODE_KEY, $delete);
    }

    public static function confirmAuthorizationCode($id, $code)
    {
        return static::checkCodeInCache($id, $code, static::AUTHORIZATION_CODE_KEY);
    }

    private static function saveToCache($id, $code, $prefixKey)
    {
        static::removeCacheData($id, $prefixKey);

        $key = static::getKey($id, $prefixKey);
        Cache::add($key, $code, static::EXPRIED_TIME);
        return static::getCacheData($id, $prefixKey);
    }

    private static function checkCodeInCache($id, $code, $prefixKey, $delete = true)
    {
        $cacheCode = static::getCacheData($id, $prefixKey);
        logger('===========check code in cache===========: ', [$id, $code, $prefixKey, $delete]);
        if ($cacheCode !== $code) {
            return false;
        }

        if ($delete) {
            static::removeCacheData($id, $prefixKey);
        }
        return true;
    }

    private static function getCacheData($id, $prefixKey)
    {
        $key = static::getKey($id, $prefixKey);

        $cacheData = null;
        if (Cache::has($key)) {
            $cacheData = Cache::get($key);
        }

        return $cacheData;
    }

    private static function removeCacheData($id, $prefixKey)
    {
        $key = static::getKey($id, $prefixKey);

        if (Cache::has($key)) {
            Cache::forget($key);
        }
    }

    private static function getKey($id, $prefix)
    {
        return "{$prefix}{$id}";
    }
}
