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
use App\Models\GameProfile;

class SessionStartingMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     *
     * @return void
     */

    protected $userId;
    protected $partnerUserId;
    protected $session;

    public function __construct($userId, $partnerUserId, $session)
    {
        $this->userId = $userId;
        $this->partnerUserId = $partnerUserId;
        $this->session = $session;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->view('emails.session_starting_email')
                    ->subject(__('emails.session_starting_email.subject'))
                    ->to($this->getReceiverEmail())
                    ->with([
                        'minutes'    => Consts::SESSION_CHECK_READY_STARTING_TIME,
                        'username'   => $this->getUsername(),
                        'gameTitle'  => $this->getGameTitle(),
                        'userLocale' => Consts::DEFAULT_LOCALE
                    ]);
    }

    private function getReceiverEmail()
    {
        return User::where('id', $this->userId)->value('email');
    }

    private function getUsername()
    {
        return User::where('id', $this->partnerUserId)->value('username');
    }

    private function getGameTitle()
    {
        return GameProfile::where('id', $this->session->game_profile_id)->first()->game->title;
    }

}
