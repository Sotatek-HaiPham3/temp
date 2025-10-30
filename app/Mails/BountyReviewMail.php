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
use App\Utils;

class BountyReviewMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     *
     * @return void
     */

    protected $userId;
    protected $bounty;
    protected $review;

    public function __construct($userId, $bounty, $review)
    {
        $this->userId = $userId;
        $this->bounty = $bounty;
        $this->review = $review;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->view('emails.bounty_review_email')
                    ->subject(__('emails.bounty_review_email.subject'))
                    ->to($this->getReceiverEmail())
                    ->with([
                        'reviewerName'          => $this->getUsername(),
                        'bountyTitle'           => $this->getBountyTitle(),
                        'rate'                  => Utils::trimFloatNumber($this->review->rate),
                        'userLocale'            => Consts::DEFAULT_LOCALE
                    ]);
    }

    private function getReceiverEmail()
    {
        return User::where('id', $this->userId)->value('email');
    }

    private function getUsername()
    {
        return User::where('id', $this->review->reviewer_id)->value('username');
    }

    private function getBountyTitle()
    {
        return $this->bounty->title;
    }

}
