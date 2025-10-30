<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Consts;

class Setting extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'key', 'value'
    ];

    public static function getValue($key, $initDefaultValue = null)
    {
        $setting = Setting::where('key', $key)->first();
        if (empty($setting)) {
            return $initDefaultValue;
        }
        return $setting->value;
    }
}
