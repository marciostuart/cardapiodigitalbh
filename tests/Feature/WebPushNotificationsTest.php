<?php

namespace Tests\Feature;

use App\Jobs\SendWebPushMessage;
use App\Models\OutboundMessage;
use App\Models\Order;
use App\Models\PushSubscription;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Venue;
use App\Services\WebPushNotifier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class WebPushNotificationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_subscription_is_validated_encrypted_and_scoped_to_its_user(): void
    {
        [$tenant] = $this->venue('localhost');
        $owner = User::factory()->create(['tenant_id' => $tenant->id, 'role' => 'owner']);
        $otherStaff = User::factory()->create(['tenant_id' => $tenant->id, 'role' => 'kitchen']);
        $endpoint = 'https://fcm.googleapis.com/fcm/send/store-device-one';
        $keys = ['p256dh' => str_repeat('A', 65), 'auth' => str_repeat('B', 24)];

        $this->actingAs($owner)->postJson(route('push.subscriptions.store'), [
            'endpoint' => $endpoint,
            'keys' => $keys,
        ])->assertCreated()->assertJsonPath('subscribed', true);

        $this->assertDatabaseCount('push_subscriptions', 1);
        $subscription = PushSubscription::sole();
        $raw = \Illuminate\Support\Facades\DB::table('push_subscriptions')->where('id', $subscription->id)->first();
        $this->assertSame($endpoint, $subscription->endpoint);
        $this->assertNotSame($endpoint, $raw->endpoint);
        $this->assertSame($owner->id, $subscription->user_id);
        $this->assertSame($tenant->id, $subscription->tenant_id);

        $this->actingAs($otherStaff)->deleteJson(route('push.subscriptions.destroy'), ['endpoint' => $endpoint])
            ->assertOk()->assertJsonPath('subscribed', false);
        $this->assertDatabaseCount('push_subscriptions', 1);

        $this->actingAs($owner)->postJson(route('push.subscriptions.store'), [
            'endpoint' => $endpoint,
            'keys' => ['p256dh' => str_repeat('C', 65), 'auth' => str_repeat('D', 24)],
        ])->assertCreated();
        $this->assertDatabaseCount('push_subscriptions', 1);
        $this->assertSame(str_repeat('C', 65), PushSubscription::sole()->public_key);

        $this->actingAs($owner)->deleteJson(route('push.subscriptions.destroy'), ['endpoint' => $endpoint])->assertOk();
        $this->assertDatabaseCount('push_subscriptions', 0);
    }

    public function test_untrusted_push_endpoints_are_rejected(): void
    {
        [$tenant] = $this->venue('localhost');
        $owner = User::factory()->create(['tenant_id' => $tenant->id, 'role' => 'owner']);

        $this->actingAs($owner)->postJson(route('push.subscriptions.store'), [
            'endpoint' => 'https://attacker.example/collect',
            'keys' => ['p256dh' => str_repeat('A', 65), 'auth' => str_repeat('B', 24)],
        ])->assertUnprocessable()->assertJsonValidationErrors('endpoint');

        $this->assertDatabaseCount('push_subscriptions', 0);
    }

    public function test_new_order_push_is_idempotent_and_contains_no_customer_details(): void
    {
        config(['services.webpush.public_key' => 'public-test-key', 'services.webpush.private_key' => 'private-test-key']);
        Queue::fake();
        [$tenant, $venue] = $this->venue('localhost');
        $owner = User::factory()->create(['tenant_id' => $tenant->id, 'role' => 'owner']);
        $endpoint = 'https://fcm.googleapis.com/fcm/send/order-device';
        PushSubscription::create([
            'tenant_id' => $tenant->id,
            'user_id' => $owner->id,
            'endpoint_hash' => hash('sha256', $endpoint),
            'endpoint' => $endpoint,
            'public_key' => str_repeat('A', 65),
            'auth_token' => str_repeat('B', 24),
            'last_used_at' => now(),
        ]);
        $order = Order::create([
            'venue_id' => $venue->id,
            'status' => 'pending',
            'customer_name' => 'Cliente confidencial',
            'customer_phone' => '31999999999',
            'subtotal' => 20,
            'total' => 20,
        ]);
        $notifier = app(WebPushNotifier::class);

        $this->assertSame(1, $notifier->queueNewOrder($order));
        $this->assertSame(0, $notifier->queueNewOrder($order));
        $this->assertDatabaseCount('outbound_messages', 1);
        $message = OutboundMessage::sole();
        $payload = json_decode($message->body, true, flags: JSON_THROW_ON_ERROR);
        $this->assertSame($tenant->id, $message->tenant_id);
        $this->assertSame($order->id, $message->order_id);
        $this->assertSame('/painel/pedidos/'.$order->id, $payload['url']);
        $this->assertStringNotContainsString('Cliente confidencial', $message->body);
        $this->assertStringNotContainsString('31999999999', $message->body);
        Queue::assertPushed(SendWebPushMessage::class, 1);
    }

    private function venue(string $subdomain): array
    {
        $tenant = Tenant::create(['name' => 'Push', 'slug' => 'push-'.str_replace('.', '-', $subdomain)]);
        $venue = Venue::create(['tenant_id' => $tenant->id, 'name' => 'Push', 'subdomain' => $subdomain]);

        return [$tenant, $venue];
    }
}
