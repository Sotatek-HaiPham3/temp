<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Jobs\ProcessSessionCheckReady;

class RunProcessSessionCheckReady extends Command
{
        /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'session_check_ready:process';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Session check ready process!';

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
        ProcessSessionCheckReady::dispatchNow();
    }
}
