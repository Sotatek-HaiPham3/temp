<?php

use Illuminate\Database\Seeder;
use App\Utils;
use App\Models\SmsWhitelist;

class SmsWhitelistSeeder extends Seeder
{

    const SMS_WHITELIST = ['us', 'ca'];


    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('sms_whitelists')->truncate();

        foreach (static::SMS_WHITELIST as $countryCode) {
            SmsWhitelist::create([
                'country_code' => $countryCode,
            ]);
        }
    }
}
