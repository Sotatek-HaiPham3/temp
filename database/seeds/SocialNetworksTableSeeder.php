<?php

use Illuminate\Database\Seeder;
use App\Utils;

class SocialNetworksTableSeeder extends Seeder
{

    private $socials = [
        [ 'type' => 'discord', 'url' => '/images/socials/discord.svg'],
        [ 'type' => 'facebook', 'url' => '/images/socials/facebook.svg'],
        [ 'type' => 'instagram', 'url' => '/images/socials/instagram.svg'],
        [ 'type' => 'paypal', 'url' => '/images/socials/paypal.svg'],
        [ 'type' => 'tiktok', 'url' => '/images/socials/tiktok.svg'],
        [ 'type' => 'twitch', 'url' => '/images/socials/twitch.svg'],
        [ 'type' => 'twitter', 'url' => '/images/socials/twitter.svg'],
        [ 'type' => 'youtube', 'url' => '/images/socials/youtube.svg']
    ];

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('social_networks_link')->truncate();

        $assetsUrl = Utils::getSchemeAndHttpHostForAssets();

        foreach ($this->socials as $item) {
            DB::table('social_networks_link')->insert(array_merge($item, [
                'url' => "{$assetsUrl}{$item['url']}"
            ]));
        }
    }
}
