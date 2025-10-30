<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Http\Services\PaypalService;

class GetPaymentDetailFromPaypal extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'paypal:get-payment-detail {id?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

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
        $paymentId = $this->getPaymentId();
        $paypalService = new paypalService();

        $paymentDetail = $paypalService->getPaymentDetailById($paymentId);

        $this->info('Paypal response: ' . $paymentDetail);

        if ($this->confirm('Do you want to get more payment detail?')) {
            $this->call('paypal:get-payment-detail');
        }
    }

    private function getPaymentId()
    {
        $paymentId = $this->argument('id');

        if (empty($paymentId)) {
            $paymentId = $this->ask('Please input paymentId');
        }

        return $paymentId;
    }
}
