<?php

namespace App\Mails;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;
use DB;
use Auth;
use App\Consts;
use App\Utils\BigNumber;
use App\Models\User;
use App\Models\GameProfileOffer;

class SessionBookedMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     *
     * @return void
     */

    protected $session;
    protected $data;

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
        return $this->view('emails.session_booked_email')
                    ->subject(__('emails.session_booked_email.subject'))
                    ->to($this->getReceiverEmail())
                    ->with([
                        'gamelancerFullname'    => $this->getFullname($this->session->gamelancer_id),
                        'userFullname'          => $this->getFullname($this->session->claimer_id),
                        'sessionType'           => $this->getSessionType(),
                        'gameProfileTitle'      => $this->getGameTitle(),
                        'userLocale'            => Consts::DEFAULT_LOCALE
                    ]);
    }

    private function getReceiverEmail()
    {
        return User::where('id', $this->session->gamelancer_id)->value('email');
    }

    private function getFullname($userId)
    {
        return User::where('id', $userId)->value('username');
    }

    private function getSessionType()
    {
        $offer = GameProfileOffer::withTrashed()->find($this->session->offer_id);

        if (empty($offer)) {
            return "FREE";
        }

        if ($offer->type === Consts::GAME_TYPE_HOUR) {
            return BigNumber::new($this->session->quantity)->toString() . " hours";
        }
        return BigNumber::new($this->session->quantity)->toString() . " games";
    }

    private function getGameTitle()
    {
        return $this->session->gameProfile->game->title;
    }

}
