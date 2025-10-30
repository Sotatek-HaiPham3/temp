<?php

namespace App\Mails;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Consts;
use Auth;
use App\Models\User;

class SendBountyClaimedMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     *
     * @return void
     */

    protected $bounty;
    protected $user;

    public function __construct($bounty)
    {
        $this->bounty = $bounty;
        $this->user = Auth::user();
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->view('emails.claimed_bounty_email')
                    ->subject(__('emails.claimed_bounty_email.subject'))
                    ->to($this->getReceiverEmail())
                    ->with([
                        'bountyTitle' => $this->bounty->title,
                        'gamelancerName' => $this->user->username,
                        'userLocale' => Consts::DEFAULT_LOCALE,
                    ]);
    }

    private function getReceiverEmail()
    {
        return User::where('id', $this->bounty->user_id)->value('email');
    }
}
