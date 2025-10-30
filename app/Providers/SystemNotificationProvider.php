<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Http\Services\SystemNotification;

class SystemNotificationProvider extends ServiceProvider {

    public function boot()
    {
        //
    }

    public function register()
    {
        $this->app->bind('systemnotification', function () {
            return new SystemNotification();
        });
   }
}
