<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Utils\BigNumber;
use App\Models\Session;
use App\Consts;

class UpdateSessionType extends Command
{

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'session_type:update';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update session type';

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
        $sessions = Session::select('sessions.*', 'game_profile_offers.price')
            ->leftJoin('game_profile_offers', 'game_profile_offers.id', 'sessions.offer_id')
            ->where('sessions.type', '')
            ->get();

        foreach ($sessions as $session) {
            $type = Consts::SESSION_TYPE_SCHEDULE;

            if (!$session->price || !BigNumber::new($session->price)->comp(0)) {
                $type = Consts::SESSION_TYPE_FREE;
            }

            if ($session->booked_at === $session->schedule_at) {
                $type = Consts::SESSION_TYPE_NOW;
            }

            $session->type = $type;
            $session->save();
        }
    }
}
