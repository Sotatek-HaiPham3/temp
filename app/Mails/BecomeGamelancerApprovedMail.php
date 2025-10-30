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

class BecomeGamelancerApprovedMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     *
     * @return void
     */

    protected $user;
    protected $hasFreeSession;

    public function __construct($user, $hasFreeSession = false)
    {
        $this->user = $user;
        $this->hasFreeSession = $hasFreeSession;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->view('emails.become_gamelancer_approved_email')
                    ->subject(__('emails.become_gamelancer_approved_email.subject'))
                    ->to($this->user->email)
                    ->with([
                        'username'              => $this->user->username,
                        'hasFreeSession'        => $this->hasFreeSession,
                        'userLocale'            => Consts::DEFAULT_LOCALE
                    ]);
    }
}
