<?php

namespace App\Mails;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class MarketingEmailQueue extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     *
     * @return void
     */

    protected $email;
    protected $title;
    protected $content;
    protected $userLocale;

    public function __construct($email, $title, $content, $userLocale)
    {
        $this->email = $email;
        $this->title = $title;
        $this->content = $content;
        $this->userLocale = $userLocale;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->view('emails.marketing_email')
                    ->subject(__('emails.marketing.subject'))
                    ->to($this->email)
                    ->with([
                        'email' => $this->email,
                        'title' => $this->title,
                        'content' => $this->content,
                        'userLocale' => $this->userLocale,
                    ]);
    }
}
