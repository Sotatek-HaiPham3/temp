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
use App\Consts;
use Exception;
use SystemNotification;

class SendSystemNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $type;
    protected $params;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($type, $params)
    {
        $this->type = $type;
        $this->params = $params;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $userId     = $this->params['user_id'];
        $type       = $this->params['type'];
        $message    = $this->params['message'];
        $props      = array_get($this->params, 'props', []);
        $data       = array_get($this->params, 'data', []);

        switch ($this->type) {
            case Consts::NOTIFY_TYPE_SESSION:
                SystemNotification::notifySessionActivity($userId, $type, $message, $props, $data);
                break;
            case Consts::NOTIFY_TYPE_VIDEO:
                SystemNotification::notifyVideoActivity($userId, $type, $message, $props, $data);
                break;
            case Consts::NOTIFY_TYPE_FAVORITE:
                SystemNotification::notifyFavoriteActivity($userId, $type, $message, $props, $data);
                break;
            case Consts::NOTIFY_TYPE_TASKING:
            case Consts::NOTIFY_TYPE_TASKING_LEVEL_UP:
            case Consts::NOTIFY_TYPE_TASKING_DAILY_CHECKIN:
                SystemNotification::notifyTasking($userId, $type, $message, $props, $data);
                break;
            case Consts::NOTIFY_TYPE_OTHER:
                SystemNotification::notifyOther($userId, $type, $message, $props, $data);
                break;
            default:
                break;
        }
    }
}
