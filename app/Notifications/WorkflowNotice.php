<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WorkflowNotice extends Notification
{
    public function __construct(public string $subject, public string $message, public string $panel = 'hei') {}

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)->subject($this->subject)->line($this->message)->action($this->panel === 'ched' ? 'Open CHED portal' : 'Open HEI portal', url($this->panel === 'ched' ? '/ched' : '/hei'));
    }
}
