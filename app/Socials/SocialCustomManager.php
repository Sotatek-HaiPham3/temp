<?php

namespace App\Socials;

use App\Socials\DiscordProvider;
use App\Socials\AppleProvider;
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
}
