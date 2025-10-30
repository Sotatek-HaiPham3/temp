<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Jobs\ProcessBounty;

class RunBountyProcessor extends Command
{

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user_bounty:proccess {--start_time} {--end_time}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'User Bounty Process';

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
        ProcessBounty::dispatchNow($startTime, $endTime);
    }
}
