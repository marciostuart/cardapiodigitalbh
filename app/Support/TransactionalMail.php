<?php

namespace App\Support;

use App\Models\EmailTemplate;
use Illuminate\Notifications\Messages\MailMessage;
use Throwable;

class TransactionalMail
{
    public static function message(string $key, object $notifiable, array $context, string $actionUrl): MailMessage
    {
        $values = array_merge([
            'name' => $notifiable->name ?? 'cliente',
            'app_name' => config('app.name'),
        ], $context);

        $template = self::template($key);
        $data = [
            'heading' => self::replace($template['heading'], $values),
            'body' => self::replace($template['body'], $values),
            'actionText' => self::replace($template['action_text'] ?? '', $values),
            'actionUrl' => $actionUrl,
            'footer' => self::replace($template['footer'] ?? '', $values),
        ];

        return (new MailMessage)
            ->subject(self::replace($template['subject'], $values))
            ->view('emails.transactional', $data)
            ->text('emails.transactional-text', $data);
    }

    private static function template(string $key): array
    {
        try {
            $template = EmailTemplate::where('key', $key)->where('active', true)->first();
            if ($template) {
                return $template->only(['subject', 'heading', 'body', 'action_text', 'footer']);
            }
        } catch (Throwable) {
            // The fallback keeps critical e-mails working during first deployment.
        }

        return self::defaults()[$key] ?? self::defaults()['welcome'];
    }

    private static function replace(string $text, array $values): string
    {
        $replacements = [];
        foreach ($values as $name => $value) {
            $replacements['{{'.$name.'}}'] = (string) $value;
        }

        return strtr($text, $replacements);
    }

    private static function defaults(): array
    {
        return [
            'welcome' => ['subject' => 'Seu Cardápio Digital BH está pronto', 'heading' => 'Boas-vindas, {{name}}!', 'body' => 'Seu cadastro foi concluído com sucesso. Acesse o painel para configurar seu estabelecimento.', 'action_text' => 'Acessar meu painel', 'footer' => 'Conte com a gente para colocar sua operação no ar.'],
            'password_reset' => ['subject' => 'Redefina sua senha no Cardápio Digital BH', 'heading' => 'Olá, {{name}}', 'body' => 'Recebemos uma solicitação para redefinir sua senha. Este link expira em {{expire_minutes}} minutos.', 'action_text' => 'Criar nova senha', 'footer' => 'Se você não fez esta solicitação, ignore esta mensagem.'],
            'email_verification' => ['subject' => 'Confirme seu e-mail no Cardápio Digital BH', 'heading' => 'Confirme seu endereço de e-mail', 'body' => 'Olá, {{name}}. Confirme seu e-mail para proteger sua conta.', 'action_text' => 'Confirmar meu e-mail', 'footer' => 'Se você não criou esta conta, ignore esta mensagem.'],
        ];
    }
}
