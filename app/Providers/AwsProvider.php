<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Http\Services\Aws;

class AwsProvider extends ServiceProvider {

    public function boot()
    {
        //
    }

    public function register()
    {
        $this->app->singleton('aws', function () {
            return new Aws();
        });
   }
}
