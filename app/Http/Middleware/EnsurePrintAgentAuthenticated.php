<?php

namespace App\Http\Middleware;

use App\Models\PrinterAgentDevice;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePrintAgentAuthenticated
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = (string) $request->bearerToken();
        abort_unless($token !== '', 401);

        $device = PrinterAgentDevice::where('token_hash', hash_hmac('sha256', $token, (string) config('app.key')))
            ->where('active', true)
            ->first();
        abort_unless($device, 401);

        if (! $device->last_seen_at || $device->last_seen_at->lt(now()->subMinute())) {
            $device->update(['last_seen_at' => now()]);
        }
        $request->attributes->set('printer_agent_device', $device);

        return $next($request);
    }
}
