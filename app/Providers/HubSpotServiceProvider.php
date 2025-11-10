<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use SevenShores\Hubspot\Factory;

class HubSpotServiceProvider extends ServiceProvider {

    public function boot()
    {
      //
    }

    public function register()
    {
     $this->app->bind('hubspot',function() {
         $config = [
            'key' => env('HUBSPOT_API_KEY'),
        ];
        $clientOptions = [
            'http_errors' => true
        ];
        return new Factory ($config, null, $clientOptions, true);
      });
   }
}
