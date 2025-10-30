<?php

namespace App\Mails;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;
use Carbon\Carbon;
use App\Consts;

class ExceptionEmail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     *
     * @return void
     */

    protected $content;

    public function __construct($content)
    {
        $this->content = $content;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $date = (string) Carbon::now('UTC');
        // $title = str_before($this->content, ' at /var/www');
        $title = 'Gamelancer';
        $appName = env('APP_NAME');
        $subject = "{$appName}-Excetpion: {$title} {$date} (UTC)";

        return  $this->text('emails.exception_report')
                    ->subject($subject)
                    ->to($this->getExceptionReportMail())
                    ->with([
                        'content' => $this->content
                    ]);
    }

    private function getExceptionReportMail()
    {
        return env('EXCEPTION_REPORT_EMAIL') ?: Consts::EXCEPTION_REPORT_EMAIL;
    }
}
