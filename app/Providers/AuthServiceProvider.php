<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Laravel\Passport\Passport;
use Laravel\Passport\Bridge\PersonalAccessGrant;
use League\OAuth2\Server\AuthorizationServer;
use DateInterval;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array
     */
    protected $policies = [
        // 'App\Model' => 'App\Policies\ModelPolicy',
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();

        $tokenExpiryTime = env('TOKEN_EXPIRE_TIME', 2);
        $refreshTokenExpiryTime = env('REFRESH_TOKEN_EXPIRE_TIME', 60);

        Passport::tokensExpireIn(now()->addDays($tokenExpiryTime));
        Passport::personalAccessTokensExpireIn(now()->addDays($tokenExpiryTime));
        Passport::refreshTokensExpireIn(now()->addDays($refreshTokenExpiryTime));
    }
}
