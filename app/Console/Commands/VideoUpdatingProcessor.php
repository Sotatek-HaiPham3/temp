<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Exception;
use App\Jobs\VideoUpdatingJob;

class VideoUpdatingProcessor extends Command
{
        /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'video-updating-processor:run';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Video Updating Processor';

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
        while (true) {
            try {
                $this->process();
            } catch (Exception $exception) {
                logger()->error($exception);
            }
            usleep(200000); // 200 ms
        }
    }

    private function process()
    {
        VideoUpdatingJob::dispatchNow();
    }
}
