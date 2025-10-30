<?php

namespace App\Mails;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class EmailResetPasswordQueue extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     *
     * @return void
     */

    protected $email;
    protected $username;
    protected $token;
    protected $userLocale;
    protected $timezoneOffset;

    public function __construct($email, $username, $token, $timezoneOffset, $userLocale)
    {
        $this->email = $email;
        $this->username = $username;
        $this->timezoneOffset = $timezoneOffset;
        $this->token = $token;
        $this->userLocale = $userLocale;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $utc = -($this->timezoneOffset)/60;
        $date = (string) \Carbon\Carbon::now($utc);
        $subject = trans('emails.reset_password_email.subject', ['date' => $date], $this->userLocale);
        return $this->view('emails.reset_password_email')
            ->subject($subject)
            ->to($this->email)
            ->with([
                'email' => $this->email,
                'username' => $this->username,
                'token'  => $this->token,
                'userLocale' => $this->userLocale
            ]);
    }
}
