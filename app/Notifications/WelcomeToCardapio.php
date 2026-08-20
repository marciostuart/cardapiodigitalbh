<?php

namespace App\Notifications;

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
        return (new MailMessage)
            ->subject('Seu Cardápio Digital BH está pronto')
            ->greeting('Olá, '.$notifiable->name.'!')
            ->line('O cadastro de '.$this->venueName.' foi concluído com sucesso no plano Grátis.')
            ->line('Agora você já pode cadastrar produtos, criar mesas e imprimir os QR Codes do estabelecimento.')
            ->action('Acessar meu painel', route('login'))
            ->line('Conte com a gente para colocar sua operação no ar.');
    }

    public function toArray(object $notifiable): array
    {
        return ['venue_name' => $this->venueName];
    }
}
