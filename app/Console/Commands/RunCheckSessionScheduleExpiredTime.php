<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Jobs\CheckSessionScheduleExpiredTime;

class RunCheckSessionScheduleExpiredTime extends Command
{
        /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'session_check_schedule_expired_time:check_time';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check Session Schedule expried time';

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
        CheckSessionScheduleExpiredTime::dispatchNow();
    }
}
