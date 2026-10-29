<?php

namespace Tests\Feature;

use App\Jobs\ExpireOnlinePayment;
use App\Jobs\SyncMercadoPagoPayment;
use App\Models\MercadoPagoConnection;
use App\Models\OnlinePayment;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Venue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class MercadoPagoPaymentsTest extends TestCase
{
    use RefreshDatabase;

    public function test_online_order_stays_out_of_kitchen_until_signed_payment_confirmation(): void
    {
        Queue::fake();
        config([
            'services.mercadopago.client_id' => 'test-client',
            'services.mercadopago.client_secret' => 'test-secret',
            'services.mercadopago.webhook_secret' => 'webhook-secret',
            'services.mercadopago.redirect_uri' => 'https://cardapiodigitalbh.com.br/painel/pagamentos/mercado-pago/retorno',
            'services.mercadopago.test_mode' => true,
        ]);
        Http::fake(['https://api.mercadopago.com/checkout/preferences' => Http::response([
            'id' => 'pref-test-1',
            'sandbox_init_point' => 'https://sandbox.mercadopago.com/checkout/1',
        ], 201)]);

        [$tenant, $venue, $product] = $this->catalog('pagamento-online');
        MercadoPagoConnection::create([
            'tenant_id' => $tenant->id,
            'seller_id' => '123456',
            'access_token' => 'seller-test-token',
            'refresh_token' => 'seller-refresh-token',
            'expires_at' => now()->addHours(3),
            'connected_at' => now(),
        ]);

        $response = $this->postJson('https://pagamento-online.cardapiodigitalbh.com.br/pedidos', [
            'channel' => 'pickup',
            'customer_name' => 'Ana',
            'payment_mode' => 'online',
            'payment_method' => 'mercadopago',
            'items' => [['product_id' => $product->id, 'quantity' => 1]],
        ])->assertCreated()->assertJsonPath('payment_mode', 'online')
            ->assertJsonPath('checkout_url', 'https://sandbox.mercadopago.com/checkout/1');

        $orderId = $response->json('order.id');
        $payment = OnlinePayment::where('order_id', $orderId)->sole();
        Queue::assertPushed(ExpireOnlinePayment::class, fn ($job) => $job->queue === 'payments');
        $this->assertSame('pending', $payment->status);
        $this->assertDatabaseHas('orders', ['id' => $orderId, 'payment_mode' => 'online', 'payment_status' => 'pending']);
        $this->get($response->json('tracking_url'))->assertOk()->assertSee('depois da confirmação do pagamento');
        $kitchen = User::factory()->create(['tenant_id' => $tenant->id, 'role' => 'kitchen']);
        $this->actingAs($kitchen)->getJson('https://pagamento-online.cardapiodigitalbh.com.br/cozinha')->assertOk()->assertJsonCount(0);

        $owner = User::factory()->create(['tenant_id' => $tenant->id, 'role' => 'owner']);
        $this->actingAs($owner)->patch(route('owner.orders.update', $orderId), ['status' => 'accepted'])
            ->assertRedirect()->assertSessionHasErrors('status');
    }

    public function test_valid_signed_webhook_is_queued_and_invalid_signature_is_rejected(): void
    {
        Queue::fake();
        config(['services.mercadopago.webhook_secret' => 'webhook-secret']);
        $dataId = '987654';
        $requestId = 'request-123';
        $timestamp = '1760000000';
        $manifest = "id:{$dataId};request-id:{$requestId};ts:{$timestamp};";
        $signature = hash_hmac('sha256', $manifest, 'webhook-secret');

        $this->postJson('/webhooks/mercado-pago?data.id='.$dataId, [
            'type' => 'payment',
            'user_id' => '123456',
            'data' => ['id' => $dataId],
        ], [
            'x-request-id' => $requestId,
            'x-signature' => 'ts='.$timestamp.',v1='.$signature,
        ])->assertAccepted();
        Queue::assertPushed(SyncMercadoPagoPayment::class, fn ($job) => $job->providerPaymentId === $dataId && $job->sellerId === '123456' && $job->queue === 'payments');

        $this->postJson('/webhooks/mercado-pago?data.id='.$dataId, [
            'type' => 'payment', 'user_id' => '123456', 'data' => ['id' => $dataId],
        ], [
            'x-request-id' => $requestId,
            'x-signature' => 'ts='.$timestamp.',v1=invalid',
        ])->assertUnauthorized();
    }

    private function catalog(string $slug): array
    {
        $tenant = Tenant::create(['name' => $slug, 'slug' => $slug]);
        $venue = Venue::create(['tenant_id' => $tenant->id, 'name' => $slug, 'subdomain' => $slug]);
        $category = ProductCategory::create(['venue_id' => $venue->id, 'name' => 'Pratos']);
        $product = Product::create(['venue_id' => $venue->id, 'product_category_id' => $category->id, 'name' => 'Executivo', 'price' => 25]);

        return [$tenant, $venue, $product];
    }
}
