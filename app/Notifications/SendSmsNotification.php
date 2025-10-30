<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use NotificationChannels\Twilio\TwilioChannel;
use NotificationChannels\Twilio\TwilioSmsMessage;

class SendSmsNotification extends Notification implements ShouldQueue
{
    use Queueable;

    private $message;
    private $mediaUrl;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(string $message, $mediaUrl = null)
    {
        $this->message = $message;
        $this->mediaUrl = $mediaUrl;
    }

    public function via()
    {
        return [TwilioChannel::class];
    }

    public function toTwilio()
    {
        $notify = (new TwilioSmsMessage())->content($this->message);
        if (!!$this->mediaUrl) {
            $notify->mediaUrl($this->mediaUrl);
        }

        return $notify;
    }
}
