<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Http\Services\StripeService;

class RegisterWebhookStripe extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'stripe_webhook:register';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Register stripe wallet webhooks';

    protected $stripeService;
    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->stripeService = new StripeService();
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->stripeService->registerWebhook();
    }
}
