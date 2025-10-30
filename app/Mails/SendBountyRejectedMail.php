<?php

namespace App\Mails;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Consts;
use Auth;
use App\Models\User;
use App\Models\Bounty;

class SendBountyRejectedMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     *
     * @return void
     */

    protected $bountyClaimRequest;
    protected $user;

    public function __construct($bountyClaimRequest)
    {
        $this->bountyClaimRequest = $bountyClaimRequest;
        $this->user = Auth::user();
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->view('emails.rejected_bounty_email')
                    ->subject(__('emails.rejected_bounty_email.subject'))
                    ->to($this->getReceiverEmail())
                    ->with([
                        'bountyTitle' => $this->getBountyTitle(),
                        'userName' => $this->user->username,
                        'userLocale' => Consts::DEFAULT_LOCALE,
                    ]);
    }

    private function getReceiverEmail()
    {
        return User::where('id', $this->bountyClaimRequest->gamelancer_id)->value('email');
    }

    private function getBountyTitle()
    {
        return Bounty::where('id', $this->bountyClaimRequest->bounty_id)->value('title');
    }
}
