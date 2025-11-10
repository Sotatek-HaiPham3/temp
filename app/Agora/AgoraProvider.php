<?php

namespace App\Agora;

use Illuminate\Support\ServiceProvider;
use SevenShores\Hubspot\Factory;
use App\Agora\Support\Model;

class AgoraProvider extends ServiceProvider {

    const EXPIRY_TIME_DEFAULT = 86400; // seconds

    public function boot()
    {
        //
    }

    public function register()
    {
        $this->app->bind('agora', function () {
            $config = config('agora');

            $appId          = $config['id'];
            $certificate    = $config['certificate'];
            $expiryTime     = empty($config['expiry_time']) ? static::EXPIRY_TIME_DEFAULT : $config['expiry_time'];

            return new Model($appId, $certificate, $expiryTime);
        });
   }
}
