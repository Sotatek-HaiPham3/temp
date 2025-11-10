<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use SevenShores\Hubspot\Factory;
use App\Http\Services\ElasticsearchService;

class ElasticsearchProvider extends ServiceProvider {

    public function boot()
    {
        //
    }

    public function register()
    {
        $this->app->bind('elasticsearch', function () {
            return new ElasticsearchService();
        });
   }
}
