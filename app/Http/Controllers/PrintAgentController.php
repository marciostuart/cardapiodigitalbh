<?php

namespace App\Http\Controllers;

use App\Models\PrintJob;
use App\Models\PrinterAgentDevice;
use App\Models\PrinterPairingCode;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PrintAgentController extends Controller
{
    public function pair(Request $request): JsonResponse
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'size:8', 'regex:/^[A-HJ-NP-Z2-9]{8}$/'],
            'name' => ['required', 'string', 'max:120'],
        ]);
        $codeHash = hash_hmac('sha256', Str::upper($data['code']), (string) config('app.key'));
        $token = bin2hex(random_bytes(32));

        $device = DB::transaction(function () use ($data, $codeHash, $token) {
            $pairing = PrinterPairingCode::where('code_hash', $codeHash)
                ->whereNull('used_at')
                ->where('expires_at', '>', now())
                ->lockForUpdate()
                ->first();
            if (! $pairing) {
                throw ValidationException::withMessages(['code' => 'Código de pareamento inválido ou expirado. Gere outro pelo painel.']);
            }

            $pairing->update(['used_at' => now()]);
            $hasPrimary = PrinterAgentDevice::where('tenant_id', $pairing->tenant_id)->where('active', true)->where('is_primary', true)->exists();

            return PrinterAgentDevice::create([
                'tenant_id' => $pairing->tenant_id,
                'venue_id' => $pairing->venue_id,
                'name' => trim($data['name']),
                'token_hash' => hash_hmac('sha256', $token, (string) config('app.key')),
                'is_primary' => ! $hasPrimary,
                'active' => true,
                'last_seen_at' => now(),
            ]);
        });

        return response()->json([
            'device' => ['id' => $device->id, 'name' => $device->name],
            'token' => $token,
        ], 201);
    }

    public function jobs(Request $request): JsonResponse
    {
        /** @var PrinterAgentDevice $device */
        $device = $request->attributes->get('printer_agent_device');
        if (! $device->is_primary) {
            return response()->json(['jobs' => []]);
        }

        $job = DB::transaction(function () use ($device) {
            $job = PrintJob::where('tenant_id', $device->tenant_id)
                ->where('venue_id', $device->venue_id)
                ->where(function ($query) use ($device) {
                    $query->where(function ($query) use ($device) {
                        $query->where('status', 'queued')->where(function ($query) use ($device) {
                            $query->whereNull('printer_agent_device_id')->orWhere('printer_agent_device_id', $device->id);
                        });
                    })->orWhere(function ($query) use ($device) {
                        $query->where('status', 'printing')->where('printer_agent_device_id', $device->id)->where('claimed_at', '<', now()->subMinutes(2));
                    });
                })
                ->oldest('created_at')
                ->lockForUpdate()
                ->first();
            if (! $job) {
                return null;
            }

            $job->update([
                'status' => 'printing',
                'printer_agent_device_id' => $device->id,
                'claimed_at' => now(),
                'attempts' => $job->attempts + 1,
            ]);

            return $job->fresh();
        });

        return response()->json(['jobs' => $job ? [[
            'id' => $job->id,
            'kind' => $job->kind,
            'payload' => $job->payload,
        ]] : []]);
    }

    public function acknowledge(Request $request, PrintJob $job): JsonResponse
    {
        /** @var PrinterAgentDevice $device */
        $device = $request->attributes->get('printer_agent_device');
        abort_unless(
            $job->tenant_id === $device->tenant_id
            && $job->venue_id === $device->venue_id
            && (int) $job->printer_agent_device_id === $device->id
            && $job->status === 'printing',
            404,
        );

        $data = $request->validate([
            'success' => ['required', 'boolean'],
            'printer_name' => ['nullable', 'string', 'max:120'],
            'error' => ['nullable', 'string', 'max:180'],
        ]);

        if ($data['success']) {
            $job->update(['status' => 'printed', 'printed_at' => now(), 'printer_name' => $data['printer_name'] ?? null, 'last_error' => null]);
        } elseif ($job->attempts < 3) {
            $job->update(['status' => 'queued', 'printer_agent_device_id' => null, 'claimed_at' => null, 'last_error' => 'Falha de impressão. Verifique a impressora e tente novamente.']);
        } else {
            $job->update(['status' => 'failed', 'last_error' => 'A impressão falhou após três tentativas. Verifique a impressora no computador da loja.']);
        }

        return response()->json(['acknowledged' => true]);
    }
}
