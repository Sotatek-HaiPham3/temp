<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Jobs\CheckSessionResponseInvitation;

class RunCheckSessionResponseInvitation extends Command
{
        /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'session_response_invitation:check_time';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check Session Response Invitation';

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
        CheckSessionResponseInvitation::dispatchNow();
    }
}
