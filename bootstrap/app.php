<?php

use App\Http\Middleware\EnsureAccountActive;
use App\Http\Middleware\EnsurePlatformOwner;
use App\Http\Middleware\EnsureRole;
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
        $middleware->append(InjectGlobalTracking::class);
        $middleware->validateCsrfTokens(except: ['webhooks/evolution']);
        $middleware->alias([
            'tenant' => ResolveTenant::class,
            'venue.access' => EnsureVenueMembership::class,
            'platform.owner' => EnsurePlatformOwner::class,
            'account.active' => EnsureAccountActive::class,
            'role' => EnsureRole::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
