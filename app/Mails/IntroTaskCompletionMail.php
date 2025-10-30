<?php

namespace App\Mails;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Consts;

class IntroTaskCompletionMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     *
     * @return void
     */

    protected $user;
    protected $coin;

    public function __construct($user, $coin)
    {
        $this->user = $user;
        $this->coin  = $coin;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->view('emails.intro_task_completion')
            ->subject(__('emails.intro_task_completion.subject'))
            ->to($this->user->email)
            ->with([
                'coin' => $this->coin,
                'userLocale' => Consts::DEFAULT_LOCALE
            ]);
    }
}
