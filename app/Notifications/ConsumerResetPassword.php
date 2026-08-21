<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ConsumerResetPassword extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly string $token, private readonly string $subdomain) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $url = route('public.consumer.password.reset', [
            'subdomain' => $this->subdomain,
            'token' => $this->token,
            'email' => $notifiable->email,
        ]);

        return (new MailMessage)
            ->subject('Redefina sua senha do Cardápio Digital BH')
            ->greeting('Olá, '.$notifiable->name.'!')
            ->line('Recebemos uma solicitação para redefinir a senha da sua conta de consumidor.')
            ->action('Criar nova senha', $url)
            ->line('Se você não solicitou esta alteração, pode ignorar esta mensagem.');
    }
}
