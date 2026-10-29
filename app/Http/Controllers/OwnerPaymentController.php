<?php

namespace App\Http\Controllers;

use App\Models\MercadoPagoConnection;
use App\Models\OnlinePayment;
use App\Services\MercadoPagoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Throwable;

class OwnerPaymentController extends Controller
{
    public function index(Request $request)
    {
        $tenant = $request->user()->tenant;
        $connection = MercadoPagoConnection::where('tenant_id', $tenant->id)->first();
        $configured = filled(config('services.mercadopago.client_id'))
            && filled(config('services.mercadopago.client_secret'))
            && filled(config('services.mercadopago.webhook_secret'));

        return view('owner.payments', compact('connection', 'configured'));
    }

    public function connect(Request $request, MercadoPagoService $mercadoPago)
    {
        abort_unless(config('services.mercadopago.client_id') && config('services.mercadopago.client_secret'), 503, 'O Mercado Pago ainda não foi configurado pela plataforma.');

        $state = Str::random(64);
        $verifier = null;
        $challenge = null;
        if (config('services.mercadopago.pkce_enabled')) {
            [$verifier, $challenge] = $mercadoPago->pkce();
        }

        Cache::put('mercadopago:oauth:'.hash('sha256', $state), [
            'tenant_id' => $request->user()->tenant_id,
            'user_id' => $request->user()->id,
            'code_verifier' => $verifier,
        ], now()->addMinutes(10));

        return redirect()->away($mercadoPago->authorizationUrl($state, $challenge));
    }

    public function callback(Request $request, MercadoPagoService $mercadoPago)
    {
        if ($request->filled('error')) {
            return redirect()->route('owner.payments.index')->withErrors(['payment' => 'A conexão foi cancelada no Mercado Pago.']);
        }

        $state = (string) $request->query('state');
        $context = $state !== '' ? Cache::pull('mercadopago:oauth:'.hash('sha256', $state)) : null;
        abort_unless(
            is_array($context)
            && (int) $context['tenant_id'] === (int) $request->user()->tenant_id
            && (int) $context['user_id'] === (int) $request->user()->id
            && $request->filled('code'),
            403,
            'A autorização expirou ou não corresponde a esta sessão. Inicie a conexão novamente.'
        );

        try {
            $tokens = $mercadoPago->exchangeAuthorizationCode(
                (string) $request->query('code'),
                $context['code_verifier'] ?? null,
            );
            abort_unless(isset($tokens['user_id'], $tokens['access_token'], $tokens['refresh_token']), 502, 'O Mercado Pago não retornou todos os dados de autorização necessários.');

            MercadoPagoConnection::updateOrCreate(
                ['tenant_id' => $request->user()->tenant_id],
                [
                    'seller_id' => (string) $tokens['user_id'],
                    'access_token' => $tokens['access_token'],
                    'refresh_token' => $tokens['refresh_token'],
                    'public_key' => $tokens['public_key'] ?? null,
                    'expires_at' => now()->addSeconds((int) ($tokens['expires_in'] ?? 0)),
                    'connected_at' => now(),
                ],
            );
        } catch (Throwable $error) {
            report($error);

            return redirect()->route('owner.payments.index')->withErrors(['payment' => 'Não foi possível conectar esta conta. Confira se a autorização foi feita na conta do estabelecimento e tente novamente.']);
        }

        return redirect()->route('owner.payments.index')->with('status', 'Conta do Mercado Pago conectada. Os clientes ainda pagarão somente pelos pedidos feitos após a configuração.');
    }

    public function disconnect(Request $request)
    {
        if (OnlinePayment::where('tenant_id', $request->user()->tenant_id)
            ->whereIn('status', ['creating', 'pending', 'failed', 'rejected', 'cancelled'])
            ->where('expires_at', '>', now())
            ->exists()) {
            return redirect()->route('owner.payments.index')->withErrors(['payment' => 'Aguarde o fim dos pagamentos pendentes antes de desconectar a conta.']);
        }

        MercadoPagoConnection::where('tenant_id', $request->user()->tenant_id)->delete();

        return redirect()->route('owner.payments.index')->with('status', 'Conta do Mercado Pago desconectada.');
    }
}
