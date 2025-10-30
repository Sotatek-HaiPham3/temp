<?php

namespace App\Mails;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Consts;
use DB;

class IntroTaskReminderMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     *
     * @return void
     */

    protected $userId;

    public function __construct($userId)
    {
        $this->userId = $userId;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $user = DB::table('users')->where('id', $this->userId)->first();

        return $this->view('emails.intro_task_reminder')
            ->subject(__('emails.intro_task_reminder.subject'))
            ->to($user->email)
            ->with([
                'userLocale' => Consts::DEFAULT_LOCALE
            ]);
    }
}
