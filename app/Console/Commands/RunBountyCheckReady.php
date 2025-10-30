<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Jobs\BountyCheckReady;

class RunBountyCheckReady extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bounty-check-ready:run {--start_time} {--end_time}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'The process which checks time confirmation ready for both gamer and gamelancer';

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
        $startTime = $this->option('start_time');
        $endTime = $this->option('end_time');
        BountyCheckReady::dispatchNow($startTime, $endTime);
    }
}
