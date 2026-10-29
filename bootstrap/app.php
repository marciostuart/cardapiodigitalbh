<?php

use App\Http\Middleware\EnsureAccountActive;
use App\Http\Middleware\EnsurePlatformOwner;
use App\Http\Middleware\EnsurePrintAgentAuthenticated;
use App\Http\Middleware\EnsureRole;
use App\Http\Middleware\EnsureTermsAccepted;
use App\Http\Middleware\EnsureVenueMembership;
use App\Http\Middleware\InjectGlobalTracking;
use App\Http\Middleware\ResolveTenant;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/media.php',
        apiPrefix: '',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Traefik and Caddy terminate HTTPS before forwarding requests to PHP-FPM.
        // Trust only the private reverse-proxy network so signed HTTPS URLs validate.
        $middleware->trustProxies(at: env('TRUSTED_PROXIES', '10.0.1.0/24'));
        $middleware->append(InjectGlobalTracking::class);
        $middleware->encryptCookies(except: ['cd_tracking_consent']);
        $middleware->validateCsrfTokens(except: ['webhooks/evolution', 'webhooks/mercado-pago']);
        $middleware->alias([
            'tenant' => ResolveTenant::class,
            'venue.access' => EnsureVenueMembership::class,
            'platform.owner' => EnsurePlatformOwner::class,
            'account.active' => EnsureAccountActive::class,
            'terms.accepted' => EnsureTermsAccepted::class,
            'role' => EnsureRole::class,
            'printer.agent' => EnsurePrintAgentAuthenticated::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
