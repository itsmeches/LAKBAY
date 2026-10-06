<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WorkflowNotice extends Notification
{
    public function __construct(public string $subject, public string $message) {}

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)->subject($this->subject)->line($this->message)->action('Open HEI portal', url('/hei'));
    }
}
