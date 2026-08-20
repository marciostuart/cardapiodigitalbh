<?php

namespace App\Notifications;

use App\Support\TransactionalMail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WelcomeToCardapio extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly string $venueName) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return TransactionalMail::message('welcome', $notifiable, [
            'venue_name' => $this->venueName,
        ], route('login'));
    }

    public function toArray(object $notifiable): array
    {
        return ['venue_name' => $this->venueName];
    }
}
