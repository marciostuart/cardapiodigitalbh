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
        ResetPassword::toMailUsing(function (object $notifiable, string $token) {
            $url = url(route('password.reset', [
                'token' => $token,
                'email' => $notifiable->getEmailForPasswordReset(),
            ], false));

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
}
