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
    protected $input;

    public function __construct($user, $userLocale, $input)
    {
        $this->user = $user;
        $this->userLocale = $userLocale;
        $this->input = $input;
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
                        'vip' => !empty($this->input['vip']),
                        'userLocale' => $this->userLocale,
                        'userName' => $this->user->username
                    ]);
    }
}
