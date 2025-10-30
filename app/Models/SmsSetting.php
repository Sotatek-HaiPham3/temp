<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Consts;

class SmsSetting extends Model
{
    protected $table = 'sms_settings';

    protected $fillable = [
        'max_price',
        'rate_limit_price',
        'rate_limit_ttl',
        'rate_limit',
        'white_list',
        'rate_list'
    ];

    public static function getData()
    {
        $setting = static::first();

        $setting->white_list = explode(Consts::CHAR_COMMA, $setting->white_list);
        $setting->rate_list = explode(Consts::CHAR_COMMA, $setting->rate_list);

        return $setting;
    }
}
