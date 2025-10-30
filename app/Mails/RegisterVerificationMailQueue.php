<?php

namespace App\Mails;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class RegisterVerificationMailQueue extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     *
     * @return void
     */

    protected $email;
    protected $userLocale;
    protected $otpCode;

    public function __construct($email, $userLocale, $otpCode)
    {
        $this->email = $email;
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
            ->to($this->email)
            ->with([
                'code'  => $this->otpCode,
                'userLocale' => $this->userLocale,
                'userName' => $this->email
            ]);
    }
}
