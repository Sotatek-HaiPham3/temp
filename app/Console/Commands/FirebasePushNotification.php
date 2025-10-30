<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Http\Services\FirebaseService;

class FirebasePushNotification extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fcm:send';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test push nofitication with Firebase';

    protected $fcmService;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->fcmService = new FirebaseService();

        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $params = [
            'title' => 'Firebase Test',
            'body' => 'Firebase Body',
            'data' => [
                'xxx' => 'yyyy'
            ],
        ];
        $this->fcmService->pushNotification(1, $params);
    }
}
