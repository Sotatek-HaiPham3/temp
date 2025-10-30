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

class SessionRejectedMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     *
     * @return void
     */

    protected $session;

    public function __construct($session)
    {
        $this->session = $session;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->view('emails.session_rejected_email')
                    ->subject(__('emails.session_rejected_email.subject'))
                    ->to($this->getReceiverEmail())
                    ->with([
                        'gamelancerFullname'    => $this->getUsername($this->session->gamelancer_id),
                        'userFullname'          => $this->getUsername($this->session->claimer_id),
                        'gameProfileTitle'      => $this->getGameTitle(),
                        'userLocale'            => Consts::DEFAULT_LOCALE
                    ]);
    }

    private function getReceiverEmail()
    {
        return User::where('id', $this->session->claimer_id)->value('email');
    }

    private function getUsername($userId)
    {
        return User::where('id', $userId)->value('username');
    }

    private function getGameTitle()
    {
        return $this->session->gameProfile->game->title;
    }

}
