<?php

namespace App\Http\Controllers;

use App\Models\PushSubscription;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class WebPushSubscriptionController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'endpoint' => ['required', 'string', 'url', 'max:2048'],
            'keys.p256dh' => ['required', 'string', 'regex:/^[A-Za-z0-9_+\/=\-]{40,256}$/'],
            'keys.auth' => ['required', 'string', 'regex:/^[A-Za-z0-9_+\/=\-]{16,128}$/'],
        ]);
        $endpoint = $data['endpoint'];
        $parts = parse_url($endpoint);
        $host = strtolower((string) ($parts['host'] ?? ''));
        $allowed = $host === 'fcm.googleapis.com'
            || $host === 'push.services.mozilla.com'
            || str_ends_with($host, '.push.services.mozilla.com')
            || $host === 'web.push.apple.com'
            || str_ends_with($host, '.push.apple.com');

        if (($parts['scheme'] ?? null) !== 'https' || isset($parts['user']) || isset($parts['pass']) || (isset($parts['port']) && $parts['port'] !== 443) || ! $allowed) {
            throw ValidationException::withMessages(['endpoint' => 'O endpoint não pertence a um serviço de notificações compatível.']);
        }

        $user = $request->user();
        $subscription = PushSubscription::updateOrCreate(
            ['endpoint_hash' => hash('sha256', $endpoint)],
            [
                'tenant_id' => $user->tenant_id,
                'user_id' => $user->id,
                'endpoint' => $endpoint,
                'public_key' => $data['keys']['p256dh'],
                'auth_token' => $data['keys']['auth'],
                'last_used_at' => now(),
            ],
        );

        return response()->json(['subscribed' => true, 'id' => $subscription->endpoint_hash], 201);
    }

    public function destroy(Request $request): JsonResponse
    {
        $data = $request->validate(['endpoint' => ['required', 'string', 'url', 'max:2048']]);
        $hash = hash('sha256', $data['endpoint']);
        PushSubscription::where('tenant_id', $request->user()->tenant_id)
            ->where('user_id', $request->user()->id)
            ->where('endpoint_hash', $hash)
            ->delete();

        return response()->json(['subscribed' => false]);
    }
}
