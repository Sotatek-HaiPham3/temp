<?php

namespace App\Mails;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class VerificationClaimBountyMailQueue extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     *
     * @return void
     */

    protected $email;
    protected $code;
    protected $userLocale;
    protected $userName;

    public function __construct($email, $code, $userLocale, $userName)
    {
        $this->email = $email;
        $this->code = $code;
        $this->userLocale= $userLocale;
        $this->userName = $userName;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->view('emails.code_of_claim_bounty_email_content')
                    ->subject(__('emails.code_of_claim_bounty_email_content.subject'))
                    ->to($this->email)
                    ->with([
                        'email' => $this->email,
                        'code'  => $this->code,
                        'userLocale' => $this->userLocale,
                        'userName' => $this->userName
                    ]);
    }
}
