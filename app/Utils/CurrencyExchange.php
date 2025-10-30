<?php

namespace App\Utils;

use App\Consts;
use App\Models\CoinPriceSetting;
use Auth;

class CurrencyExchange
{
    // public static function usdToCoin($amount)
    // {
    //     list($source, $target) = static::getRate(CoinPriceSetting::SETTING_RATE_USD_COIN_KEY);
    //     return BigNumber::new($amount)->mul($target)->div($source)->toString();
    // }

    public static function coinToBar($amount)
    {
        list($source, $target) = static::getRate(CoinPriceSetting::SETTING_RATE_COIN_BAR_KEY);
        return BigNumber::new($amount)->mul($target)->div($source)->toString();
    }

    public static function barToCoin($amount)
    {
        // reverse ratio of coin_bar.
        list($source, $target) = static::getRate(CoinPriceSetting::SETTING_RATE_BAR_COIN_KEY);
        return BigNumber::new($amount)->mul($target)->div($source)->toString();
    }

    public static function barToUsd($amount)
    {
        list($source, $target) = static::getRate(CoinPriceSetting::SETTING_RATE_BAR_USD_KEY);
        return BigNumber::new($amount)->mul($target)->div($source)->toString();
    }

    public static function usdToBar($amount)
    {
        list($target, $source) = static::getRate(CoinPriceSetting::SETTING_RATE_BAR_USD_KEY);
        return BigNumber::new($amount)->mul($target)->div($source)->toString();
    }

    public static function coinToUsd($amount)
    {
        list($target, $source) = static::getRate(CoinPriceSetting::SETTING_RATE_COIN_USD_KEY);
        return BigNumber::new($amount)->mul($target)->div($source)->toString();
        // $bar = static::coinToBar($amount);
        // return static::barToUsd($bar);
    }

    public static function eurToBar($amount)
    {
        // TODO: Need to rate between eur and bar.
        return $amount;
    }

    private static function getRate($key)
    {
        $region = Auth::user()->region ?? CoinPriceSetting::REGION_DEFAULT;
        $rateSetting = CoinPriceSetting::where('region', $region)->first();
        $value = $rateSetting->$key;
        return explode(Consts::CHAR_COLON, $value);
    }
}
