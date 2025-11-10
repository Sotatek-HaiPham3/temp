<?php

namespace App\Forums\Services;

use Illuminate\Support\ServiceProvider;
use SevenShores\Hubspot\Factory;
use App\Forums\Services\Nodebb;
use App\Models\Setting;
use App\Consts;

class NodebbProvider extends ServiceProvider {

    public function boot()
    {
        //
    }

    public function register()
    {
        $this->app->bind('nodebb', function () {
            $categoryPostId = Setting::getValue(Consts::NODEBB_CATEGORY_POST_ID_KEY);
            $categoryVideoId = Setting::getValue(Consts::NODEBB_CATEGORY_VIDEO_ID_KEY);
            return new Nodebb($categoryPostId, $categoryVideoId);
        });
   }
}
