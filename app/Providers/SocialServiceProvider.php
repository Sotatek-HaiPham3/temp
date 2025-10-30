<?php

namespace App\Providers;

use Laravel\Socialite\SocialiteServiceProvider;
use Laravel\Socialite\Contracts\Factory;
use App\Socials\SocialCustomManager;

class SocialServiceProvider extends SocialiteServiceProvider
{
    public function register()
    {
        $this->app->bind(Factory::class, function ($app) {
            return new SocialCustomManager($app);
        });
    }
}
