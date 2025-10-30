<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Http\Services\ContentApplication;

class ContentApplicationProvider extends ServiceProvider {

    public function boot()
    {
        //
    }

    public function register()
    {
        $this->app->bind('content.application', function () {
            return new ContentApplication();
        });
   }
}
