<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Jobs\BountyCheckReady;
use App\Models\MattermostUser;
use App\Utils;
use Mattermost;
use DB;

class FixEmailMattermostUser extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mattermost-user-email:fix';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fixing the email mattermost';


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
        if (Utils::isProduction()) {
            return;
        }

        DB::beginTransaction();
        try {
            $users = $this->getUsers();

            $mattermostUserIds = $users->pluck('mattermost_user_id')->toArray();

            $mattermostUsers = Mattermost::getUsersByIds($mattermostUserIds);

            collect($mattermostUsers)->each(function ($item) use ($users) {

                $user = array_get($users, $item->id, null);

                if (empty($user)) {
                    return;
                }

                $username = $user->user ? $user->user->username : null;
                if (!$username) {
                    return;
                }

                $email = $item->email;
                if (!str_contains($email, '@gamelancer.com')) {
                    $email = strtolower(Utils::generateAutoEmail());

                    logger()->info("==============[mattermostUserUpdate]===old email: ", [$item->email]);
                    logger()->info("==============[mattermostUserUpdate]===new email: ", [$email]);

                    Mattermost::updateEmailUser($item->id, $item->email, $email);
                }

                $user->mattermost_email = $email;
                $user->save();
            });

            DB::commit();
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    private function getUsers()
    {
        $mattermostUsers = MattermostUser::with(['user'])->get();

        return $mattermostUsers->mapWithKeys(function ($item) {
            return [$item->mattermost_user_id => $item];
        });
    }
}
