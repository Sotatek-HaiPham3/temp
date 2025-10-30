<?php

use App\Http\Services\MasterdataService;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Migrations\Migration;

class MasterDataSocialNumberToSettingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::table('settings')->whereIn('key', ['total_monthly_views',
            'total_followers',
            'snapchat_subscribers',
            'snapchat_egirl_subscribers',
            'snapchat_minecraft_subscribers',
            'snapchat_gta_subscribers',
            'snapchat_gamelancer_subscribers',
            'snapchat_gaming_subscribers',
            'snapchat_game_gear_subscribers',
            'tiktok_gaming_followers',
            'tiktok_gaming_likes',
            'tiktok_gamelancer_followers',
            'tiktok_gamelancer_likes',
            'tiktok_gamer_followers',
            'tiktok_gamer_likes',
            'tiktok_egirl_followers',
            'tiktok_egirl_likes']
        )->delete();

        $data = [
            ['key' => 'total_monthly_views', 'value' => '1 BILLION+'],
            ['key' => 'total_followers', 'value' => '26 MILLION+'],
            ['key' => 'snapchat_subscribers', 'value' => '2 MILLION+'],
            ['key' => 'snapchat_egirl_subscribers', 'value' => '1,500,000+'],
            ['key' => 'snapchat_minecraft_subscribers', 'value' => '340,000+'],
            ['key' => 'snapchat_gta_subscribers', 'value' => '362,000+'],
            ['key' => 'snapchat_gamelancer_subscribers', 'value' => '261,000+'],
            ['key' => 'snapchat_gaming_subscribers', 'value' => '32,000+'],
            ['key' => 'snapchat_game_gear_subscribers', 'value' => '172,000+'],
            ['key' => 'tiktok_gaming_followers', 'value' => '9,000,000'],
            ['key' => 'tiktok_gaming_likes', 'value' => '420,000,000'],
            ['key' => 'tiktok_gamelancer_followers', 'value' => '4,400,000'],
            ['key' => 'tiktok_gamelancer_likes', 'value' => '246,000,000'],
            ['key' => 'tiktok_gamer_followers', 'value' => '4,300,000'],
            ['key' => 'tiktok_gamer_likes', 'value' => '141,000,000'],
            ['key' => 'tiktok_egirl_followers', 'value' => '1,900,000'],
            ['key' => 'tiktok_egirl_likes', 'value' => '59,000,000'],
        ];

        foreach ($data as $datum) {
            DB::table('settings')->insert($datum);
        }
        MasterdataService::clearCacheOneTable('settings');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        return;
    }
}
