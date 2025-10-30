<?php

namespace App\Mails;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class WelcomeMail extends Mailable
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
        return $this->view('emails.welcome_email')
                    ->subject(__('emails.welcome_email.subject'))
                    ->to($this->user->email)
                    ->with([
                        'email' => $this->user->email,
                        'userLocale' => $this->userLocale,
                        'userName' => $this->user->userName
                    ]);
    }
}
