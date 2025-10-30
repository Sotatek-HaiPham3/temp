<?php

namespace App\Mails;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class OtpMailQueue extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     *
     * @return void
     */

    protected $user;
    protected $userLocale;
    protected $otpCode;

    public function __construct($user, $userLocale, $otpCode)
    {
        $this->user = $user;
        $this->userLocale = $userLocale;
        $this->otpCode = $otpCode;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->view('emails.otp_email_content')
                    ->subject(__('emails.otp_email_content.subject'))
                    ->to($this->user->email)
                    ->with([
                        'email' => $this->user->email,
                        'code'  => $this->otpCode,
                        'userLocale' => $this->userLocale,
                        'userName' => $this->user->username
                    ]);
    }
}
