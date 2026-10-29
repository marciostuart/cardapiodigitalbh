<?php

namespace App\Services;

use App\Models\MercadoPagoConnection;
use App\Models\OnlinePayment;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

class MercadoPagoService
{
    private const API = 'https://api.mercadopago.com';

    public function authorizationUrl(string $state, ?string $codeChallenge): string
    {
        $clientId = (string) config('services.mercadopago.client_id');
        $redirectUri = (string) config('services.mercadopago.redirect_uri');
        if ($clientId === '' || $redirectUri === '') {
            throw new RuntimeException('Mercado Pago ainda não está configurado no ambiente.');
        }

        $query = [
            'client_id' => $clientId,
            'response_type' => 'code',
            'platform_id' => 'mp',
            'state' => $state,
            'redirect_uri' => $redirectUri,
        ];
        if ($codeChallenge !== null) {
            $query['code_challenge'] = $codeChallenge;
            $query['code_challenge_method'] = 'S256';
        }

        return 'https://auth.mercadopago.com/authorization?'.http_build_query($query, '', '&', PHP_QUERY_RFC3986);
    }

    public function exchangeAuthorizationCode(string $code, ?string $codeVerifier): array
    {
        $data = [
            'client_id' => (string) config('services.mercadopago.client_id'),
            'client_secret' => (string) config('services.mercadopago.client_secret'),
            'grant_type' => 'authorization_code',
            'code' => $code,
            'redirect_uri' => (string) config('services.mercadopago.redirect_uri'),
            'test_token' => (bool) config('services.mercadopago.test_mode') ? 'true' : 'false',
        ];
        if ($codeVerifier !== null) {
            $data['code_verifier'] = $codeVerifier;
        }

        return $this->postOAuth($data);
    }

    public function createPreference(OnlinePayment $payment): array
    {
        $payment->loadMissing('order.venue', 'order.items');
        $order = $payment->order;
        $connection = MercadoPagoConnection::where('tenant_id', $payment->tenant_id)->firstOrFail();
        $now = now();
        $expiresAt = $now->copy()->addMinutes(30);
        $trackUrl = route('public.order.track', [
            'subdomain' => $order->venue->subdomain,
            'token' => $order->public_token,
        ]);

        $preferenceData = [
                'items' => [[
                    'id' => (string) $order->id,
                    'title' => 'Pedido #'.$order->id.' - '.$order->venue->name,
                    'description' => 'Pedido online do estabelecimento '.$order->venue->name,
                    'quantity' => 1,
                    'currency_id' => 'BRL',
                    'unit_price' => (float) $payment->amount,
                ]],
                'external_reference' => $payment->external_reference,
                'metadata' => ['order_id' => $order->id, 'tenant_id' => $payment->tenant_id],
                'back_urls' => [
                    'success' => $trackUrl.'?pagamento=retorno',
                    'pending' => $trackUrl.'?pagamento=retorno',
                    'failure' => $trackUrl.'?pagamento=retorno',
                ],
                'auto_return' => 'approved',
                'notification_url' => route('webhooks.mercado-pago'),
                'expires' => true,
                'expiration_date_from' => $now->toIso8601String(),
                'expiration_date_to' => $expiresAt->toIso8601String(),
            ];
        if ($order->customer?->email) {
            $preferenceData['payer'] = ['email' => $order->customer->email];
        }

        $response = Http::acceptJson()
            ->asJson()
            ->withToken($this->accessToken($connection))
            ->withHeaders(['X-Idempotency-Key' => $payment->external_reference])
            ->timeout(15)
            ->post(self::API.'/checkout/preferences', $preferenceData);

        if (! $response->successful()) {
            throw new RuntimeException('Não foi possível criar a cobrança no Mercado Pago. Verifique a conexão do lojista e tente novamente.');
        }

        $result = $response->json();
        $checkoutUrl = config('services.mercadopago.test_mode')
            ? ($result['sandbox_init_point'] ?? null)
            : ($result['init_point'] ?? null);
        if (! is_string($checkoutUrl) || ! str_starts_with($checkoutUrl, 'https://')) {
            throw new RuntimeException('O Mercado Pago não retornou um endereço seguro para pagamento.');
        }

        $payment->update([
            'preference_id' => (string) ($result['id'] ?? ''),
            'checkout_url' => $checkoutUrl,
            'status' => 'pending',
            'expires_at' => $expiresAt,
        ]);
        \App\Jobs\ExpireOnlinePayment::dispatch($payment->id)->delay($expiresAt)->onQueue('payments');
        return ['checkout_url' => $checkoutUrl, 'expires_at' => $expiresAt];
    }

    public function searchPayment(string $externalReference, MercadoPagoConnection $connection): ?array
    {
        $response = Http::acceptJson()
            ->withToken($this->accessToken($connection))
            ->timeout(10)
            ->get(self::API.'/v1/payments/search', [
                'external_reference' => $externalReference,
                'sort' => 'date_created',
                'criteria' => 'desc',
                'limit' => 1,
            ]);

        if (! $response->successful()) {
            throw new RuntimeException('Não foi possível confirmar a cobrança antes de liberar o estoque.');
        }

        return $response->json('results.0');
    }

    public function getPayment(string $providerPaymentId, MercadoPagoConnection $connection): array
    {
        $response = Http::acceptJson()
            ->withToken($this->accessToken($connection))
            ->timeout(10)
            ->get(self::API.'/v1/payments/'.rawurlencode($providerPaymentId));

        if (! $response->successful()) {
            throw new RuntimeException('Não foi possível consultar a confirmação no Mercado Pago.');
        }

        return $response->json();
    }

    public function accessToken(MercadoPagoConnection $connection): string
    {
        if ($connection->expires_at && $connection->expires_at->isAfter(now()->addMinutes(5))) {
            return $connection->access_token;
        }

        return Cache::lock('mercadopago:refresh:'.$connection->tenant_id, 30)->block(10, function () use ($connection) {
            $fresh = MercadoPagoConnection::findOrFail($connection->id);
            if ($fresh->expires_at && $fresh->expires_at->isAfter(now()->addMinutes(5))) {
                return $fresh->access_token;
            }

            $data = $this->postOAuth([
                'client_id' => (string) config('services.mercadopago.client_id'),
                'client_secret' => (string) config('services.mercadopago.client_secret'),
                'grant_type' => 'refresh_token',
                'refresh_token' => $fresh->refresh_token,
                'test_token' => (bool) config('services.mercadopago.test_mode') ? 'true' : 'false',
            ]);
            $fresh->update([
                'access_token' => $data['access_token'],
                'refresh_token' => $data['refresh_token'],
                'expires_at' => now()->addSeconds((int) $data['expires_in']),
            ]);

            return $fresh->access_token;
        });
    }

    public function pkce(): array
    {
        $verifier = rtrim(strtr(base64_encode(random_bytes(48)), '+/', '-_'), '=');
        $challenge = rtrim(strtr(base64_encode(hash('sha256', $verifier, true)), '+/', '-_'), '=');

        return [$verifier, $challenge];
    }

    private function postOAuth(array $data): array
    {
        if ($data['client_id'] === '' || $data['client_secret'] === '') {
            throw new RuntimeException('Preencha as credenciais do Mercado Pago no arquivo .env do ambiente.');
        }

        $response = Http::acceptJson()->asJson()->timeout(12)->post(self::API.'/oauth/token', $data);
        $result = $response->json();
        if (! $response->successful() || ! is_array($result) || empty($result['access_token'])) {
            throw new RuntimeException('Não foi possível autenticar no Mercado Pago. Confira as credenciais e a configuração OAuth/PKCE.');
        }

        return $result;
    }
}
