<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Http\Services\Mattermost;
use App\Models\Setting;
use App\Consts;

class MattermostProvider extends ServiceProvider {

    public function boot()
    {
        //
    }

    public function register()
    {
        $this->app->bind('mattermost', function () {
            $mattermostTeamId = Setting::getValue(Consts::MATTERMOST_TEAM_ID_KEY);
            return new Mattermost($mattermostTeamId);
        });
   }
}
