<?php

use Illuminate\Database\Seeder;
use App\Models\CoinPriceSetting;

class CoinPriceSettingsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('coin_price_settings')->truncate();
        DB::table('coin_price_settings')->insert([
            [
                'region'        => CoinPriceSetting::REGION_DEFAULT,
                'bar_to_usd'    => '1000:7', // 1000 bars = $7
                'bar_to_coin'   => '10:8', // coins = bars * 80%
                'coin_to_bar'   => '1:1', // 1000 coins = 1000 bars
                'coin_to_usd'   => '100:1', // 100 coins = $1
            ],
        ]);
    }
}
