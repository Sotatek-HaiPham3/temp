<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CoinPriceSetting extends Model
{
    const SETTING_RATE_COIN_BAR_KEY = 'coin_to_bar';
    const SETTING_RATE_BAR_USD_KEY = 'bar_to_usd';
    const SETTING_RATE_BAR_COIN_KEY = 'bar_to_coin';
    const SETTING_RATE_COIN_USD_KEY = 'coin_to_usd';
    const REGION_DEFAULT = 'default';

    protected $table = 'coin_price_settings';

    protected $fillable = ['region', 'bar_to_coin', 'bar_to_usd', 'coin_to_bar'];
}
