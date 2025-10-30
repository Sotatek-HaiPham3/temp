<?php

namespace App\Mails;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class VerificationMailQueue extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     *
     * @return void
     */

    protected $user;
    protected $userLocale;

    public function __construct($user, $userLocale)
    {
        $this->user = $user;
        $this->userLocale = $userLocale;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->view('emails.confirmation_email_content')
                    ->subject(__('emails.confirmation_email_content.subject'))
                    ->to($this->user->email)
                    ->with([
                        'email' => $this->user->email,
                        'code'  => $this->user->email_verification_code,
                        'userLocale' => $this->userLocale,
                        'userName' => $this->user->username
                    ]);
    }
}
