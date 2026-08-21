<?php

namespace Tests\Feature;

use App\Models\Coupon;
use App\Models\DeliveryZone;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Venue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OmnichannelOrderTest extends TestCase
{
    use RefreshDatabase;

    public function test_delivery_totals_coupon_and_address_are_calculated_on_server(): void
    {
        [$tenant, $venue, $product] = $this->catalog('delivery');
        $venue->update(['delivery_enabled' => true, 'minimum_order' => 20]);
        $zone = DeliveryZone::create(['venue_id' => $venue->id, 'name' => 'Centro', 'fee' => 7]);
        Coupon::create(['venue_id' => $venue->id, 'code' => 'DEZ', 'type' => 'percent', 'value' => 10, 'minimum_order' => 20]);
        $payload = ['channel' => 'delivery', 'customer_name' => 'Ana', 'customer_phone' => '31999999999', 'whatsapp_consent' => false, 'payment_method' => 'pix', 'coupon_code' => 'dez', 'delivery_zone_id' => $zone->id, 'postal_code' => '30110-000', 'street' => 'Rua A', 'number' => '10', 'neighborhood' => 'Centro', 'city' => 'Belo Horizonte', 'state' => 'MG', 'items' => [['product_id' => $product->id, 'quantity' => 2]]];

        $response = $this->postJson('http://delivery.cardapiodigitalbh.com.br/pedidos', $payload)->assertCreated();
        $response->assertJsonPath('order.subtotal', '50.00')->assertJsonPath('order.discount', '5.00')->assertJsonPath('order.delivery_fee', '7.00')->assertJsonPath('order.total', '52.00');
        $this->assertDatabaseHas('customers', ['venue_id' => $venue->id, 'phone' => '5531999999999', 'name' => 'Ana']);
        $this->assertDatabaseHas('customer_addresses', ['postal_code' => '30110-000', 'city' => 'Belo Horizonte']);
        $this->get($response->json('tracking_url'))->assertOk()->assertSee('Pedido #');
    }

    public function test_disabled_delivery_channel_is_rejected(): void
    {
        [, , $product] = $this->catalog('no-delivery');
        $this->postJson('http://no-delivery.cardapiodigitalbh.com.br/pedidos', ['channel' => 'delivery', 'customer_name' => 'Ana', 'customer_phone' => '31999999999', 'payment_method' => 'pix', 'delivery_zone_id' => 1, 'postal_code' => '30110-000', 'street' => 'Rua A', 'number' => '10', 'neighborhood' => 'Centro', 'city' => 'BH', 'state' => 'MG', 'items' => [['product_id' => $product->id, 'quantity' => 1]]])->assertUnprocessable();
    }

    public function test_public_order_allows_optional_name_and_phone_without_whatsapp_consent(): void
    {
        [, $venue, $product] = $this->catalog('optional-contact');
        $venue->update(['pickup_enabled' => true]);

        $this->postJson('http://optional-contact.cardapiodigitalbh.com.br/pedidos', [
            'channel' => 'pickup', 'payment_method' => 'pix',
            'items' => [['product_id' => $product->id, 'quantity' => 1]],
        ])->assertCreated();

        $this->assertDatabaseHas('orders', ['venue_id' => $venue->id, 'customer_name' => null, 'customer_phone' => null]);
        $this->assertDatabaseCount('customers', 0);
    }

    public function test_owner_cannot_update_an_order_from_another_tenant(): void
    {
        [$tenantA, $venueA, $productA] = $this->catalog('tenant-one');
        [, $venueB] = $this->catalog('tenant-two');
        $ownerA = User::factory()->create(['tenant_id' => $tenantA->id, 'role' => 'owner']);
        $orderB = $venueB->hasMany(\App\Models\Order::class)->create(['status' => 'pending', 'subtotal' => 10, 'total' => 10]);
        $this->actingAs($ownerA)->patch(route('owner.orders.update', $orderB), ['status' => 'accepted'])->assertNotFound();
    }

    public function test_owner_can_only_apply_the_next_order_statuses(): void
    {
        [$tenant, $venue] = $this->catalog('order-statuses');
        $owner = User::factory()->create(['tenant_id' => $tenant->id, 'role' => 'owner']);
        $order = Order::create(['venue_id' => $venue->id, 'status' => 'pending', 'subtotal' => 10, 'total' => 10]);

        $this->actingAs($owner)->patch(route('owner.orders.update', $order), ['status' => 'preparing'])
            ->assertRedirect()
            ->assertSessionHasErrors('status');

        $this->actingAs($owner)->patch(route('owner.orders.update', $order), ['status' => 'accepted'])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'accepted']);
    }

    public function test_owner_can_edit_open_order_details_but_terminal_orders_are_read_only(): void
    {
        [$tenant, $venue, $product] = $this->catalog('order-details');
        $owner = User::factory()->create(['tenant_id' => $tenant->id, 'role' => 'owner']);
        $order = Order::create(['venue_id' => $venue->id, 'status' => 'pending', 'subtotal' => 25, 'total' => 25]);
        $order->items()->create(['product_id' => $product->id, 'name_snapshot' => $product->name, 'unit_price' => 25, 'quantity' => 1, 'total' => 25]);

        $this->actingAs($owner)->get(route('owner.orders.show', $order))->assertOk()->assertSee('Detalhes do pedido');
        $this->actingAs($owner)->patch(route('owner.orders.details.update', $order), ['notes' => 'Sem cebola', 'kitchen_notes' => 'Caprichar', 'delivery_notes' => 'Interfone 12', 'items' => [['product_id' => $product->id, 'quantity' => 2]]])->assertRedirect();
        $this->assertDatabaseHas('orders', ['id' => $order->id, 'notes' => 'Sem cebola', 'kitchen_notes' => 'Caprichar', 'delivery_notes' => 'Interfone 12', 'subtotal' => 50, 'total' => 50]);

        $order->update(['status' => 'completed']);
        $this->actingAs($owner)->get(route('owner.orders.show', $order))->assertOk()->assertSee('consulta disponível, sem edição.');
        $this->actingAs($owner)->patch(route('owner.orders.details.update', $order), ['items' => [['product_id' => $product->id, 'quantity' => 1]]])->assertUnprocessable();
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
