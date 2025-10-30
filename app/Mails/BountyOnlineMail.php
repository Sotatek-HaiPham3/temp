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
use App\Models\Game;

class BountyOnlineMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     *
     * @return void
     */

    protected $bounty;

    public function __construct($bounty)
    {
        $this->bounty = $bounty;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->view('emails.bounty_online_email')
                    ->subject(__('emails.bounty_online_email.subject'))
                    ->to($this->getReceiverEmail())
                    ->with([
                        'gameTitle'             => $this->getGameTitle(),
                        'userLocale'            => Consts::DEFAULT_LOCALE
                    ]);
    }

    private function getReceiverEmail()
    {
        return User::where('id', $this->bounty->user_id)->value('email');
    }

    private function getGameTitle()
    {
        return Game::where('id', $this->bounty->game_id)->value('title');
    }

}
