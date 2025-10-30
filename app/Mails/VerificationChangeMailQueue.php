<?php

namespace App\Mails;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class VerificationChangeMailQueue extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     *
     * @return void
     */

    protected $changeEmailHistory;
    protected $userName;
    protected $userLocale;

    public function __construct($changeEmailHistory, $userName, $userLocale)
    {
        $this->changeEmailHistory = $changeEmailHistory;
        $this->userName = $userName;
        $this->userLocale = $userLocale;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->view('emails.confirmation_change_email_content')
                    ->subject(__('emails.confirmation_change_email_content.subject'))
                    ->to($this->changeEmailHistory->new_email)
                    ->with([
                        'code'  => $this->changeEmailHistory->email_verification_code,
                        'userLocale' => $this->userLocale,
                        'userName' => $this->userName
                    ]);
    }
}
