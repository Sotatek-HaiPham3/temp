<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\UserFollowing;
use Illuminate\Console\Command;
use App\Jobs\VideosCounter;

class RemoveFollowingIdNotExist extends Command
{
        /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'remove-following-not-exist:run';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check and remove following id not exist';

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
        $dataNotExist = UserFollowing::select('following_id')->leftJoin('users', 'users.id', 'user_following.following_id')->whereNull('users.id')->pluck('following_id');
        if ($this->confirm('These following_ids(user_id) ' . implode(",", [$dataNotExist]). ' do not exist or have been deleted, do you want to delete?')) {
            UserFollowing::whereIn('following_id', $dataNotExist)->delete();
            $this->comment('Deleted success.');
        }
    }
}
