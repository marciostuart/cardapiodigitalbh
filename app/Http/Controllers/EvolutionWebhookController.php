<?php

namespace App\Http\Controllers;

use App\Models\EvolutionInstance;
use App\Services\EvolutionApi;
use Illuminate\Http\Request;

class EvolutionWebhookController extends Controller
{
    public function __invoke(Request $request)
    {
        $configured = (string) config('services.evolution.webhook_secret');
        abort_unless($configured !== '' && hash_equals($configured, (string) $request->header('X-Webhook-Secret')), 403);
        $name = (string) ($request->input('instance') ?? data_get($request->all(), 'instance.instanceName') ?? '');
        if ($name !== '' && ($instance = EvolutionInstance::where('instance_name', $name)->first())) {
            $state = EvolutionApi::state($request->all());
            $instance->update(['status' => $state, 'connected_at' => $state === 'connected' ? ($instance->connected_at ?: now()) : null, 'last_checked_at' => now(), 'last_error' => null]);
        }
        return response()->json(['received' => true]);
    }
}
