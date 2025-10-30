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

class NewBountyMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     *
     * @return void
     */

    protected $userId;
    protected $bounty;

    public function __construct($userId, $bounty)
    {
        $this->userId = $userId;
        $this->bounty = $bounty;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->view('emails.new_bounty_email')
                    ->subject(__('emails.new_bounty_email.subject'))
                    ->to($this->getReceiverEmail())
                    ->with([
                        'username'          => $this->getUsername(),
                        'title'             => $this->bounty->title,
                        'userLocale'        => Consts::DEFAULT_LOCALE
                    ]);
    }

    private function getReceiverEmail()
    {
        return User::where('id', $this->userId)->value('email');
    }

    private function getUsername()
    {
        return User::where('id', $this->bounty->user_id)->value('username');
    }
}
