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

class BecomeGamelancerRejectedMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     *
     * @return void
     */

    protected $gamelancerInfo;

    public function __construct($gamelancerInfo)
    {
        $this->gamelancerInfo = $gamelancerInfo;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->view('emails.become_gamelancer_rejected_email')
                    ->subject(__('emails.become_gamelancer_rejected_email.subject'))
                    ->to($this->getReceiverEmail())
                    ->with([
                        'username'              => $this->getUsername(),
                        'userLocale'            => Consts::DEFAULT_LOCALE
                    ]);
    }

    private function getReceiverEmail()
    {
        return User::where('id', $this->gamelancerInfo->user_id)->value('email');
    }

    private function getUsername()
    {
        return User::where('id', $this->gamelancerInfo->user_id)->value('username');
    }

}
