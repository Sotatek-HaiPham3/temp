<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Mails\IntroTaskReminderMail;
use Mail;
use App\Consts;
use Cache;
use DB;
use App\Models\UserRanking;

class IntroTaskReminderCommand extends Command
{
        /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'intro-task-reminder:run';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Intro Task Reminder Monitoring';

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
        $userRankings = $this->getUserRankings();

        foreach ($userRankings as $userRanking) {
            Mail::queue(new IntroTaskReminderMail($userRanking->id));
        }
    }

    private function getUserRankings()
    {
        return DB::table('users')
            ->select('users.*')
            ->leftJoin('user_rankings', 'user_rankings.user_id', 'users.id')
            ->where(function ($query) {
                $query->where('user_rankings.intro_step', '<', Consts::TOTAL_INTRO_STEPS)
                    ->orWhereNull('user_rankings.intro_step');
            })
            ->get();
    }
}
