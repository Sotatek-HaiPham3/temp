<?php

namespace App\Providers;

use Illuminate\Notifications\NotificationServiceProvider as BaseNotificationServiceProvider;
use Illuminate\Notifications\Channels\DatabaseChannel as IlluminateDatabaseChannel;
use App\Channels\DatabaseChannel;

class NotificationServiceProvider extends BaseNotificationServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        parent::boot();

        // Custom DatabaseChannel
        // $this->app->instance(IlluminateDatabaseChannel::class, new DatabaseChannel);
    }
}
