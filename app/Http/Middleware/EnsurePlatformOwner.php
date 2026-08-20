<?php
namespace App\Http\Middleware;
use Closure; use Illuminate\Http\Request;
class EnsurePlatformOwner { public function handle(Request $request, Closure $next) { abort_unless($request->user()?->role === 'platform_owner', 403); return $next($request); } }
