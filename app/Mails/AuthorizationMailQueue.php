<?php

namespace App\Mails;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class AuthorizationMailQueue extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     *
     * @return void
     */

    protected $user;
    protected $userLocale;
    protected $code;

    public function __construct($user, $userLocale, $code)
    {
        $this->user = $user;
        $this->userLocale = $userLocale;
        $this->code = $code;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->view('emails.authorization_code_email_content')
                    ->subject(__('emails.authorization_code_email_content.subject'))
                    ->to($this->user->email)
                    ->with([
                        'email' => $this->user->email,
                        'code'  => $this->code,
                        'userLocale' => $this->userLocale,
                        'userName' => $this->user->username
                    ]);
    }
}
