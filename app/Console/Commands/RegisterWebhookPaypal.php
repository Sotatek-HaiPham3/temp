<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Http\Services\PaypalService;

class RegisterWebhookPaypal extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'paypal_webhook:register';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Register paypal wallet webhooks';

    protected $paypalService;
    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->paypalService = new PaypalService();
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->paypalService->registerWebhook();
    }
}
