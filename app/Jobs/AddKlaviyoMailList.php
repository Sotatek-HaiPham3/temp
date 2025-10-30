<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;
use App\Http\Services\KlaviyoService;
use App\Consts;
use Exception;

class AddKlaviyoMailList implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $user;
    protected $action;
    protected $klaviyoService;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($user, $action = Consts::KALVIYO_ACTION_ADD)
    {
        $this->user = $user;
        $this->action = $action;
        $this->klaviyoService = new KlaviyoService();
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        switch ($this->action) {
            case Consts::KALVIYO_ACTION_ADD:
                $this->klaviyoService->addUser($this->user);
                break;
            case Consts::KALVIYO_ACTION_UPDATE:
                $this->klaviyoService->updateProfile($this->user);
                break;
        }
    }
}
