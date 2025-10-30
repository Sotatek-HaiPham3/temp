<?php

use Illuminate\Database\Seeder;
use App\Utils;

class BannerSeeder extends Seeder
{

    const ASSETS = [
        'images/banners/welcome.png',
        'images/banners/becom-gamelancer.png',
        'images/banners/community.png',
        'images/banners/feedback.png',
        'images/banners/giveaway.png',
        'images/banners/shop.png',
        'images/banners/submit-content.png'
    ];


    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $hostname = Utils::getSchemeAndHttpHostForAssets();

        DB::table('banners')->truncate();

        foreach (static::ASSETS as $assetsUrl) {
             DB::table('banners')->insert([
                'thumbnail'         => "{$hostname}/{$assetsUrl}",
                'link'              => $hostname
            ]);
        }
    }
}
