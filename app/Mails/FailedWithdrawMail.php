<?php

namespace App\Mails;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;
use DB;
use Auth;
use App\Consts;
use App\Models\User;
use App\Models\UserCreditCard;
use App\Utils\BigNumber;
// use App\Utils\BankName;

class FailedWithdrawMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     *
     * @return void
     */

    protected $transaction;

    public function __construct($transaction)
    {
        $this->transaction = $transaction;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $amount = BigNumber::new($this->transaction->real_amount)->toString();
        return $this->view('emails.failed_withdraw_email')
                    ->subject(__('emails.failed_withdraw_email.subject'))
                    ->to($this->getReceiverEmail())
                    ->with([
                        'amount'       => $amount,
                        'email'        => $this->transaction->paypal_receiver_email,
                        'userLocale'   => Consts::DEFAULT_LOCALE,
                    ]);
    }

    private function getReceiverEmail()
    {
        return User::where('id', $this->transaction->user_id)->value('email');
    }

    // private function getBankTitle()
    // {
    //     if ($this->transaction->payment_type === Consts::PAYMENT_TYPE_PAYPAL) {
    //         $user_card = UserCreditCard::where('id', $this->transaction->card_id)->first();
    //         return BankName::getPaypalName($user_card->card_name, $user_card->email);
    //     }

    //     if ($this->transaction->payment_type === Consts::PAYMENT_TYPE_CREDIT_CARD) {
    //         $user_card = UserCreditCard::where('id', $this->transaction->card_id)->first();
    //         return BankName::getPaypalName($user_card->card_name, $user_card->card_number);
    //     }

    //     return '';
    // }
}
