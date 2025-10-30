<?php

namespace App\Mails;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;
use DB;
use Auth;
use App\Consts;
use App\Models\User;

class ChangePasswordMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     *
     * @return void
     */

    protected $user;

    public function __construct($user)
    {
        $this->user = $user;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->view('emails.change_password_email')
                    ->subject(__('emails.change_password_email.subject'))
                    ->to($this->user->email)
                    ->with([
                        'userLocale'            => Consts::DEFAULT_LOCALE
                    ]);
    }

}
