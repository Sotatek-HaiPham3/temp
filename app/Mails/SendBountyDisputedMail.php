<?php

namespace App\Mails;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Consts;
use Auth;
use App\Models\User;

class SendBountyDisputedMail extends Mailable
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
        return $this->view('emails.disputed_bounty_email')
                    ->subject(__('emails.disputed_bounty_email.subject'))
                    ->to($this->getReceiveEmail())
                    ->with([
                        'bountyTitle' => $this->bounty->title,
                        'userName' => $this->getUsername(),
                        'userLocale' => Consts::DEFAULT_LOCALE,
                    ]);
    }

    private function getReceiveEmail()
    {
        $user = User::findOrFail($this->bounty->claimBountyRequest->gamelancer_id);

        return $user->email;
    }

    private function getUsername()
    {
        $user = User::findOrFail($this->bounty->user_id);

        return $user->username;
    }
}
