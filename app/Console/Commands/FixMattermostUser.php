<?php

namespace App\Console\Commands;

use App\Jobs\CreateMattermostUserEndpoint;
use Illuminate\Console\Command;
use App\Jobs\BountyCheckReady;
use App\Models\MattermostUser;
use App\Models\User;
use App\Utils;
use Mattermost;
use DB;

class FixMattermostUser extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mattermost-user:fix';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fixing the mattermost user missing on application';


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
        if (Utils::isProduction() && !$this->confirm('Do you wish to continue?')) {
            return;
        }

        DB::beginTransaction();
        try {
            $dataNotExist = User::select(DB::raw("users.*"))->leftJoin('mattermost_users', 'mattermost_users.user_id', 'users.id')->whereNull('mattermost_users.user_id')->get();
            foreach ($dataNotExist as $user) {
                CreateMattermostUserEndpoint::dispatchNow($user);
            }

            DB::commit();
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }
}
