<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Jobs\ProcessSessionCompleted;

class RunProcessSessionCompleted extends Command
{
        /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'session_completed:process';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process Session completed!';

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
        ProcessSessionCompleted::dispatchNow();
    }
}
