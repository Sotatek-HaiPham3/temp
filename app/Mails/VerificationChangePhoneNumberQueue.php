<?php

namespace App\Mails;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Consts;

class VerificationChangePhoneNumberQueue extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     *
     * @return void
     */

    protected $user;
    protected $changePhoneNumberHistory;

    public function __construct($user, $changePhoneNumberHistory)
    {
        $this->user = $user;
        $this->changePhoneNumberHistory = $changePhoneNumberHistory;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->view('emails.confirmation_change_phone_content')
                    ->subject(__('emails.confirmation_change_phone_content.subject'))
                    ->to($this->user->email)
                    ->with([
                        'code'  => $this->changePhoneNumberHistory->verification_code,
                        'userLocale' => Consts::DEFAULT_LOCALE
                    ]);
    }
}
