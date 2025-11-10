<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Jobs\CreateNodebbUserEndpoint;
use App\Models\User;
use App\Models\NodebbUser;
use App\Models\ChangeEmailHistory;
use App\Consts;
use Nodebb;

class FixUpdateEmailForNodebbUsers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fix-update-email-nodebb-users';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fix update email nodebb users';

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
        try {
            $userIds = $this->getUserIdsHasChangeEmailHistory();
            $userIds = $this->getUserIdsHasExistsAccountNodebb($userIds);
            $users = $this->getUsers($userIds);
            $users->each(function ($user) {
                $nodebbUserId = $user->nodebbUser->nodebb_user_id;
                $userNodebb = Nodebb::getUserInfo($nodebbUserId, $user->username);
                if (empty($userNodebb)) {
                    return;
                }

                if ($userNodebb->email === $user->email) {
                    return;
                }

                Nodebb::updateEmailBySystem($nodebbUserId, $user->email);
            });
        } catch (Exception $e) {
            throw $e;
        }
    }

    private function getUsers($userIds)
    {
        return User::select('id', 'email', 'username')
            ->whereIn('id', $userIds)
            ->get();
    }

    private function getUserIdsHasExistsAccountNodebb($userIds)
    {
        return NodebbUser::whereIn('user_id', $userIds)->pluck('user_id');
    }

    private function getUserIdsHasChangeEmailHistory()
    {
        return ChangeEmailHistory::withTrashed()
            ->where('email_verified', Consts::TRUE)
            ->where('created_at', '>=', '2020-11-10')
            ->pluck('user_id');
    }
}
