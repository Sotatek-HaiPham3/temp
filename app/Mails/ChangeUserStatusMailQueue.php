<?php

namespace App\Mails;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class ChangeUserStatusMailQueue extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     *
     * @return void
     */

    protected $email;
    protected $status;
    protected $userLocale;
    protected $userName;

    public function __construct($email, $status, $userLocale, $userName)
    {
        $this->email = $email;
        $this->status = $status;
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
        return $this->view('emails.change_user_status_email')
                    ->subject(__('emails.change_user_status_email.subject'))
                    ->to($this->email)
                    ->with([
                        'email' => $this->email,
                        'status'  => $this->status,
                        'userLocale' => $this->userLocale,
                        'userName' => $this->userName
                    ]);
    }
}
