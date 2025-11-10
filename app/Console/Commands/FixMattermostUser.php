<?php

namespace App\Console\Commands;

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
            $this->getUsers()
                ->map(function ($user, $key) {
                    $email = strtolower($user->email);
                    $mattermostUser = Mattermost::getUserByEmail($email);

                    if (!$mattermostUser) {
                        logger()->info("==============[FixMattermostUser]===mattermost user isn't exists");
                        return;
                    }

                    logger()->info('==============[FixMattermostUser]===Fixing for user: ', [
                        'user_id' => $user->id,
                        'mattermost_user_id' => $mattermostUser->id
                    ]);
                    MattermostUser::create([
                        'user_id' => $user->id,
                        'mattermost_user_id' => $mattermostUser->id
                    ]);
                });

            DB::commit();
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    private function getUsers()
    {
        $userIds = MattermostUser::pluck('user_id');
        return User::whereNotIn('id', $userIds)->get();
    }
}
