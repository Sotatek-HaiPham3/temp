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

class BookingSessionWhenGamelancerOfflineMail extends Mailable
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
        return $this->view('emails.booking_session_gamelancer_offline_email')
                    ->subject(__('emails.booking_session_gamelancer_offline_email.subject'))
                    ->to($this->getReceiverEmail())
                    ->with([
                        'username'   => $this->getFullname(),
                        'userLocale' => Consts::DEFAULT_LOCALE
                    ]);
    }

    private function getReceiverEmail()
    {
        return User::where('id', $this->gameProfile->user_id)->value('email');
    }

    private function getFullname()
    {
        return User::where('id', $this->userId)->value('username');
    }
}
