<?php

namespace App\Providers;

use App\Support\TransactionalMail;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        VerifyEmail::createUrlUsing(function (object $notifiable): string {
            $relativeUrl = URL::temporarySignedRoute(
                'verification.verify',
                now()->addMinutes(config('auth.verification.expire', 60)),
                [
                    'id' => $notifiable->getKey(),
                    'hash' => sha1($notifiable->getEmailForVerification()),
                ],
                absolute: false,
            );

            return $this->withCanonicalAppRoot(fn (): string => URL::to($relativeUrl));
        });

        ResetPassword::toMailUsing(function (object $notifiable, string $token) {
            $url = $this->withCanonicalAppRoot(fn (): string => url(route('password.reset', [
                'token' => $token,
                'email' => $notifiable->getEmailForPasswordReset(),
            ], false)));

            return TransactionalMail::message('password_reset', $notifiable, [
                'expire_minutes' => config('auth.passwords.'.config('auth.defaults.passwords').'.expire'),
            ], $url);
        });

        VerifyEmail::toMailUsing(fn (object $notifiable, string $url) => TransactionalMail::message(
            'email_verification',
            $notifiable,
            [],
            $url,
        ));

        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }
    }

    /**
     * Generate account emails from APP_URL, not the internal proxy request host/port.
     */
    private function withCanonicalAppRoot(callable $callback): mixed
    {
        $parts = parse_url((string) config('app.url'));
        $scheme = strtolower($parts['scheme'] ?? 'https');
        $host = $parts['host'] ?? 'localhost';
        $port = $parts['port'] ?? null;

        if (($scheme === 'https' && $port === 443) || ($scheme === 'http' && $port === 80)) {
            $port = null;
        }

        $root = $scheme.'://'.$host.($port ? ':'.$port : '').($parts['path'] ?? '');

        URL::forceRootUrl(rtrim($root, '/'));

        try {
            return $callback();
        } finally {
            URL::forceRootUrl(null);
        }
    }
}
