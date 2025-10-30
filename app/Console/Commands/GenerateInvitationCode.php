<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Consts;
use App\Utils;
use Exception;
use App\Jobs\GenerateInvitationCodeJob;

class GenerateInvitationCode extends Command
{
        /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'invitation_code:generate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate Invitation Code';

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
        $count = 0;
        while ($count < 3) {
            try {
                $this->process();
                return true;
            } catch (Exception $exception) {
                logger()->error($exception);
                $count ++;
            }
            usleep(200000); // 200 ms
        }
    }

    private function process()
    {
        GenerateInvitationCodeJob::dispatchNow();
    }
}
