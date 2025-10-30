<?php

namespace App\Providers;

use App\OAuth\Bridge\Repositories\ScopeRepository;
use Laravel\Passport\Passport;
use App\OAuth\CustomRequestProvider;
use App\OAuth\Bridge\Grant\CustomRequest;
use Illuminate\Support\ServiceProvider;
use App\OAuth\CustomRequestProviderInterface;
use League\OAuth2\Server\AuthorizationServer;
use Laravel\Passport\Bridge\RefreshTokenRepository;

class OAuthServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->extend(AuthorizationServer::class, function ($server, $app) {
            return tap($server, function ($server) {
                $server->enableGrantType(
                    $grantType = $this->makeSocialGrant(), Passport::tokensExpireIn()
                );

                // Allow all scopes to be requested for this grant
                $grantType->setScopeRepository(
                    $this->app->make(ScopeRepository::class)
                );
            });
        });

        $this->app->singleton(CustomRequestProviderInterface::class, CustomRequestProvider::class);
    }

    /**
     * Create and configure and instance of Social Grant.
     *
     * @return \App\OAuth\Bridge\Grant\CustomRequest
    */
    protected function makeSocialGrant()
    {
        $grant = new CustomRequest(
            $this->app->make(CustomRequestProviderInterface::class),
            $this->app->make(RefreshTokenRepository::class)
        );

        return $grant;
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}
