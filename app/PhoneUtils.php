<?php

namespace App;

use Exception;
use libphonenumber\PhoneNumberFormat;
use Propaganistas\LaravelPhone\PhoneNumber;
use Illuminate\Validation\ValidationException;
use App\Models\SmsWhitelist;
use App\Http\Services\MasterdataService;

class PhoneUtils
{
    public static function makePhoneNumber($phoneNumber, $phoneCountryCode, $format = PhoneNumberFormat::E164) {
        try {
            return PhoneNumber::make($phoneNumber, $phoneCountryCode)->format($format);
        } catch (Exception $e) {
            throw ValidationException::withMessages([
                'phone_number' => [__('exceptions.phone_number.invalid')]
            ]);
        }
    }

    public static function allowSmsNotification($user)
    {
        return MasterdataService::getOneTable('sms_whitelists')->contains(function ($record) use ($user) {
            return strtolower($record->country_code) === strtolower($user->phone_country_code);
        });
    }
}
