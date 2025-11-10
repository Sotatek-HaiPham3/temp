<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Jobs\CreateNodebbUserEndpoint;
use App\Models\User;
use App\Models\NodebbUser;
use App\Consts;

class FixCreateNodebbUsers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fix-create-nodebb-users';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fix create nodebb users';

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
            $userIds = $this->getUserIdsHasExistsAccountNodebb();
            $users = $this->getUsers($userIds);
            $users->each(function ($user) {
                CreateNodebbUserEndpoint::dispatch($user)->onQueue(Consts::CREATE_NODEBB_USER_ENDPOINT_QUEUE);
            });
        } catch (Exception $e) {
            throw $e;
        }
    }

    private function getUsers($userIds)
    {
        return User::select('id', 'email', 'username')
            ->whereNotIn('id', $userIds)
            ->get();
    }

    private function getUserIdsHasExistsAccountNodebb()
    {
        return NodebbUser::pluck('user_id');
    }
}
