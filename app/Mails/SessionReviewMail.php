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

class SessionReviewMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     *
     * @return void
     */

    protected $userId;
    protected $session;
    protected $review;

    public function __construct($userId, $session, $review)
    {
        $this->userId = $userId;
        $this->session = $session;
        $this->review = $review;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->view('emails.session_review_email')
                    ->subject(__('emails.session_review_email.subject'))
                    ->to($this->getReceiverEmail())
                    ->with([
                        'reviewerName'          => $this->getUsername(),
                        'gameTitle'             => $this->getGameTitle(),
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

    private function getGameTitle()
    {
        return $this->session->gameProfile->game->title;
    }

}
