<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Redis;
use App\Traits\UserOnlineTrait;

class RedisChannelSubscribe extends Command
{

    use UserOnlineTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'redis-channel:subscribe';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Subscribe to a Redis channel';

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
        try {
            Redis::psubscribe(['*'], function ($data) {
                $data = json_decode($data, true);
                $this->storeUsersOnline($data);
            });
        } catch (\Exception $ex) {
            // logger()->error($ex);
            usleep(200000);
            $now = now();
            $message = "======== {$now} - RedisSubscribe restarting...";
            logger()->info($message);
            echo "$message \n" ;

            $this->call('redis:subscribe');
        }
    }
}

