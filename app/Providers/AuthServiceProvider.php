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

        $tokenExpiryTime = env('TOKEN_EXPRY_TIME', 6);
        Passport::tokensExpireIn(now()->addMonths($tokenExpiryTime));
        Passport::refreshTokensExpireIn(now()->addMonths($tokenExpiryTime));

        $this->registerPersonalAccess($tokenExpiryTime);
    }

    public function registerPersonalAccess($tokenExpiryTime)
    {
        $lifetime = new DateInterval("PT{$tokenExpiryTime}H");
        app()->get(AuthorizationServer::class)->enableGrantType(
            new PersonalAccessGrant(),
            $lifetime
        );
    }
}
