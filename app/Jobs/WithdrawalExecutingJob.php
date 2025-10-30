<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Consts;
use Exception;
use App\Http\Services\TransactionService;
use App\Models\UserCreditCard;

class WithdrawalExecutingJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $transaction;
    private $transactionService;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($transaction)
    {
        $this->transaction = $transaction;
        $this->transactionService = new TransactionService;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        try {
            switch ($this->transaction->payment_type) {
                case Consts::PAYMENT_TYPE_PAYPAL:
                    // $card_number = UserCreditCard::where('id', $this->transaction->card_id)->value('email');
                    return $this->transactionService->withdrawPaypal($this->transaction);
                case Consts::PAYMENT_TYPE_CREDIT_CARD:
                    return $this->transactionService->withdrawStripe($this->transaction);
                default:
                    throw new Exception(__('payment.invalid_type'));
            }
        } catch (Exception $ex) {
            $this->transaction->status = Consts::TRANSACTION_STATUS_FAILED;
            $this->transaction->memo = Consts::TRANSACTION_MEMO_WITHDRAW_FAILED;
            $this->transaction->error_detail = $ex;
            $this->transaction->save();
        }
    }
}
