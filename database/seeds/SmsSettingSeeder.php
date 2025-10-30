<?php

use Illuminate\Database\Seeder;
use App\Models\SmsSetting;

class SmsSettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('sms_settings')->truncate();

        SmsSetting::create([
            'max_price' => 0.028,
            'rate_limit_price' => 0.015,
            'rate_limit_ttl' => 300,
            'rate_limit' => 8,
            'white_list' => 'US, CA',
            'rate_list' => 'VN'
        ]);
    }
}
