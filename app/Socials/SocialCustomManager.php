<?php

namespace App\Socials;

use App\Socials\DiscordProvider;
use App\Socials\AppleProvider;
use App\Socials\SnapchatProvider;
use App\Socials\TiktokProvider;
use Laravel\Socialite\SocialiteManager;

class SocialCustomManager extends SocialiteManager
{
    protected function createDiscordDriver()
    {
        $config = $this->app['config']['services.discord'];

        return $this->buildProvider(
            DiscordProvider::class, $config
        );
    }

    protected function createAppleDriver()
    {
        $config = $this->app['config']['services.apple'];

        return $this->buildProvider(
            AppleProvider::class, $config
        );
    }

    protected function createSnapchatDriver()
    {
        $config = $this->app['config']['services.snapchat'];

        return $this->buildProvider(
            SnapchatProvider::class, $config
        );
    }

    protected function createTiktokDriver()
    {
        $config = $this->app['config']['services.tiktok'];

        return $this->buildProvider(
            TiktokProvider::class, $config
        );
    }
}
