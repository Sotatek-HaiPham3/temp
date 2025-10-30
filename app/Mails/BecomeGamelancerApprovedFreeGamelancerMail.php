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

class BecomeGamelancerApprovedFreeGamelancerMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     *
     * @return void
     */

    protected $user;
    protected $game;

    public function __construct($user, $game = null)
    {
        $this->user = $user;
        $this->game = $game;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->view('emails.become_gamelancer_approved_as_free_email')
                    ->subject(__('emails.become_gamelancer_approved_as_free_email.subject'))
                    ->to($this->user->email)
                    ->with([
                        'username'              => $this->user->username,
                        'game'                  => $this->game,
                        'userLocale'            => Consts::DEFAULT_LOCALE
                    ]);
    }
}
