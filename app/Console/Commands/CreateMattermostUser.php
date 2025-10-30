<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Jobs\BountyCheckReady;
use App\Models\MattermostUser;
use App\Models\User;
use App\Utils;
use Mattermost;
use DB;

class CreateMattermostUser extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mattermost-user-create';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create mattermost user for testing';

    const USERNAME_BOT = 'bot';

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
            User::with(['mattermostUser'])
                ->where('username', 'like', '%' . self::USERNAME_BOT . '%')
                ->get()
                ->map(function ($user, $key) {
                    $mattermostUser = Mattermost::getUserByUsername($user->username);

                    if (!$mattermostUser) {
                        $mattermostUser = Mattermost::createUserEndpoint($user->email, $user->username);
                    }
                    $email = property_exists($mattermostUser, 'email') && !empty($mattermostUser->email)
                        ? strtolower($mattermostUser->email)
                        : (isset($user->email) ? strtolower($user->email) : strtolower(Utils::generateAutoEmail()));

                    MattermostUser::create([
                        'user_id' => $user->id,
                        'mattermost_user_id' => $mattermostUser->id,
                        'mattermost_email' => $email
                    ]);
                });

            DB::commit();
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }
}
