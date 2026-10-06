<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class InvitationNotice extends Notification
{
    public function __construct(public string $token) {}

    public function via($n): array
    {
        return ['mail'];
    }

    public function toMail($n): MailMessage
    {
        return (new MailMessage)->subject('Your LAKBAY invitation')->line('CHED has invited you to LAKBAY. This link expires in three days.')->action('Accept invitation', url('/invitations/'.$this->token));
    }
}
