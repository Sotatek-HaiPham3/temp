<?php

namespace App\Mails;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Consts;
use App\Utils;
use App\Models\User;
use App\Utils\BigNumber;
use App\Http\Services\StripeService;

class DepositPaymentMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     *
     * @return void
     */

    protected $transaction;
    protected $user;
    protected $paymentMethodId;

    public function __construct($transaction, $paymentMethodId = null)
    {
        $this->transaction = $transaction;
        $this->paymentMethodId = $paymentMethodId;
        $this->user = $this->getUser();
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->view('emails.deposit_payment_email')
                    ->subject(__('emails.deposit_payment_email.subject'))
                    ->to($this->user->email)
                    ->with([
                        'releaseAmount'         => $this->formatAmount($this->transaction->real_amount),
                        'receiveAmount'         => $this->formatAmount($this->transaction->amount),
                        'username'              => $this->user->username,
                        'paymentType'           => $this->getPaymentType(),
                        'memo'                  => $this->transaction->memo,
                        'transactionId'         => $this->transaction->transaction_id,
                        'date'                  => $this->getTransactionDate(),
                        'toEmail'               => $this->user->email,
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

    private function getPaymentType()
    {
        $paymentType = $this->transaction->payment_type;

        if ($paymentType === Consts::PAYMENT_TYPE_PAYPAL) {
            return strtoupper($paymentType);
        }

        $stripeService = new StripeService();
        $last4NumberOfCard = $stripeService->getLast4NumberOfCard($this->paymentMethodId);

        return sprintf('%s - %s', strtoupper($paymentType), $last4NumberOfCard);
    }
}
