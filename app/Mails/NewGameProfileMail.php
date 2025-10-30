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

class NewGameProfileMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     *
     * @return void
     */

    protected $userId;
    protected $gameProfile;

    public function __construct($userId, $gameProfile)
    {
        $this->userId = $userId;
        $this->gameProfile = $gameProfile;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->view('emails.new_game_profile_email')
                    ->subject(__('emails.new_game_profile_email.subject'))
                    ->to($this->getReceiverEmail())
                    ->with([
                        'username'              => $this->getUsername(),
                        'gameTitle'             => $this->getGameTitle(),
                        'userLocale'            => Consts::DEFAULT_LOCALE
                    ]);
    }

    private function getReceiverEmail()
    {
        return User::where('id', $this->userId)->value('email');
    }

    private function getUsername()
    {
        return User::where('id', $this->gameProfile->user_id)->value('username');
    }

    private function getGameTitle()
    {
        return Game::where('id', $this->gameProfile->game_id)->value('title');
    }

}
