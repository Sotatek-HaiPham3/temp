<?php

namespace App\Mails;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Consts;
use App\Models\User;
use App\Utils;
use App\Utils\BigNumber;

class ApprovedWithdrawMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     *
     * @return void
     */

    protected $transaction;
    protected $user;

    public function __construct($transaction)
    {
        $this->transaction = $transaction;
        $this->user = $this->getUser();
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->view('emails.approved_withdraw_email')
                    ->subject(__('emails.approved_withdraw_email.subject'))
                    ->to($this->user->email)
                    ->with([
                        'paypalReceiverEmail' => $this->transaction->paypal_receiver_email,
                        'releaseAmount'         => $this->formatAmount($this->transaction->real_amount),
                        'receiveAmount'         => $this->formatAmount($this->transaction->amount),
                        'username'              => $this->user->username,
                        'paymentType'           => strtoupper($this->transaction->payment_type),
                        'transactionId'         => $this->transaction->transaction_id,
                        'date'                  => $this->getTransactionDate(),
                        'userLocale'            => Consts::DEFAULT_LOCALE,
                    ]);
    }

    private function getUser()
    {
        return User::find($this->transaction->user_id);
    }

    private function getTransactionDate()
    {
        $date = $this->transaction->created_at;
        return Utils::millisecondsToCarbon($date)->format('M d, Y');
    }

    private function formatAmount($amount)
    {
        return BigNumber::new($amount)->toString();
    }
}
