<?php

namespace App\Mails;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class VerificationChangeUsernameQueue extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     *
     * @return void
     */

    protected $email;
    protected $userLocale;
    protected $changeUsernameHistory;

    public function __construct($changeUsernameHistory, $email, $userLocale)
    {
        $this->email = $email;
        $this->userLocale = $userLocale;
        $this->changeUsernameHistory = $changeUsernameHistory;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->view('emails.confirmation_change_username_content')
                    ->subject(__('emails.confirmation_change_username_content.subject'))
                    ->to($this->email)
                    ->with([
                        'code'  => $this->changeUsernameHistory->verification_code,
                        'userLocale' => $this->userLocale
                    ]);
    }
}
