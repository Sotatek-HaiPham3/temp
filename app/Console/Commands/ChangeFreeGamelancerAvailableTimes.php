<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\GamelancerAvailableTime;
use App\Consts;
use DB;

class ChangeFreeGamelancerAvailableTimes extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'available-time-free-gamelancer:change';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Change Available Time for Free Gamelancer';

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

        DB::beginTransaction();
        try {
            $userIds = User::leftJoin('gamelancer_available_times', 'gamelancer_available_times.user_id', 'users.id')
                ->where('users.user_type', Consts::USER_TYPE_FREE_GAMELANCER)
                ->whereNull('gamelancer_available_times.user_id')
                ->pluck('users.id');

            foreach ($userIds as $key => $userId) {
                for ($i=0; $i < 7; $i++) {
                    GamelancerAvailableTime::insert([
                        'user_id' => $userId,
                        'from' => 1440 * $i,
                        'to' => 1440 * ($i + 1)
                    ]);
                }
            }

            DB::commit();
        } catch (\Exception $ex) {
            DB::rollback();
            throw $ex;
        }
    }
}
