<?php

namespace App\Jobs;

use App\Consts;
use App\Utils;
use App\Events\SampleTestEvent;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;
use Mail;
use App\Mails\ExceptionEmail;

class ExceptionEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $content;

    /**
     * Create a new job instance.
     *
     * @param $userId
     * @param $currencies
     */
    public function __construct($content)
    {
        $this->content = $content;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        Mail::queue(new ExceptionEmail($this->content));
    }

}
