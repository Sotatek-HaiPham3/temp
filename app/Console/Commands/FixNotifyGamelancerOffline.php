<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cache;
use App\Models\User;
use App\Models\SystemNotification;
use App\Consts;

class FixNotifyGamelancerOffline extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notify_offline:fix';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fixing gamelancer offline notification message props';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $results = SystemNotification::where('message_key', Consts::NOTIFY_SESSION_BOOK_NOW_GAMELANCER_OFFLINE)->get();

        $usernames = $results->pluck('message_props.username');
        $users = User::withoutAppends()->whereIn('username', $usernames)->get()->mapWithKeys(function ($user) {
            return [$user['username'] => $user];
        })->all();

        foreach ($results as $notify) {
            if (empty($notify->data->user)) {
                continue;
            }


            $username = $notify->message_props->username;
            if (!empty($username) && !empty($users[$username])) {
                $user = $users[$username];

                $cloneData = clone $notify->data;
                $cloneData->user = $user;

                $notify->data = $cloneData;
                $notify->save();
            }
        }
    }
}
