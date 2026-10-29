<?php

namespace App\Http\Controllers;

use App\Jobs\SyncMercadoPagoPayment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MercadoPagoWebhookController extends Controller
{
    public function __invoke(Request $request)
    {
        $type = (string) ($request->input('type') ?: $request->query('topic', ''));
        if (! in_array($type, ['payment', 'mp-connect'], true)) {
            return response()->json(['received' => true]);
        }

        $dataId = (string) (data_get($request->input('data'), 'id')
            ?: $request->query('data.id')
            ?: $request->query('data_id', ''));
        $requestId = (string) $request->header('x-request-id', '');
        $signature = (string) $request->header('x-signature', '');
        $secret = (string) config('services.mercadopago.webhook_secret');

        if ($dataId === '' || $requestId === '' || $secret === '' || ! $this->validSignature($signature, $requestId, $dataId, $secret)) {
            Log::warning('Mercado Pago webhook rejected: invalid signature or missing fields.');

            return response()->json(['message' => 'Invalid webhook signature.'], 401);
        }

        $sellerId = (string) $request->input('user_id', '');
        if ($sellerId === '' || ! ctype_digit($sellerId)) {
            return response()->json(['received' => true]);
        }

        SyncMercadoPagoPayment::dispatch($dataId, $sellerId)->onQueue('payments');

        return response()->json(['received' => true], 202);
    }

    private function validSignature(string $header, string $requestId, string $dataId, string $secret): bool
    {
        $parts = [];
        foreach (explode(',', $header) as $part) {
            [$key, $value] = array_pad(explode('=', trim($part), 2), 2, null);
            if ($key !== null && $value !== null) {
                $parts[$key] = $value;
            }
        }

        if (empty($parts['ts']) || empty($parts['v1'])) {
            return false;
        }

        $manifest = 'id:'.$dataId.';request-id:'.$requestId.';ts:'.$parts['ts'].';';
        $expected = hash_hmac('sha256', $manifest, $secret);

        return hash_equals(strtolower($expected), strtolower($parts['v1']));
    }
}
