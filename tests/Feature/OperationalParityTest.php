<?php

namespace Tests\Feature;

use App\Models\EvolutionInstance;
use App\Models\InventoryItem;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Tenant;
use App\Models\Venue;
use App\Models\VenueSchedule;
use App\Services\InventoryService;
use App\Services\WhatsappNotifier;
use App\Support\VenueAvailability;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class OperationalParityTest extends TestCase
{
    use RefreshDatabase;

    public function test_schedule_supports_ranges_across_midnight(): void
    {
        [, $venue] = $this->venue('night');
        VenueSchedule::create(['venue_id' => $venue->id, 'day_of_week' => 1, 'opens_at' => '18:00', 'closes_at' => '02:00']);
        $this->assertCount(1, $venue->schedules()->where('active', true)->get());
        $timezone = $venue->timezone ?: 'America/Sao_Paulo';
        $this->assertTrue(VenueAvailability::isOpen($venue, CarbonImmutable::parse('2026-08-24 23:00', $timezone)), 'segunda 23h deveria estar aberta');
        $this->assertTrue(VenueAvailability::isOpen($venue, CarbonImmutable::parse('2026-08-25 01:00', $timezone)), 'terça 1h deveria herdar o intervalo de segunda');
        $this->assertFalse(VenueAvailability::isOpen($venue, CarbonImmutable::parse('2026-08-25 03:00', $timezone)));
    }

    public function test_order_reserves_product_and_ingredient_stock_and_blocks_overselling(): void
    {
        [, $venue] = $this->venue('stock');
        $category = ProductCategory::create(['venue_id' => $venue->id, 'name' => 'Lanches']);
        $product = Product::create(['venue_id' => $venue->id, 'product_category_id' => $category->id, 'name' => 'Burger', 'price' => 20, 'track_stock' => true, 'stock_on_hand' => 2]);
        $ingredient = InventoryItem::create(['venue_id' => $venue->id, 'name' => 'Carne', 'unit' => 'g', 'stock_on_hand' => 300]);
        $product->ingredients()->attach($ingredient->id, ['quantity' => 100]);
        $table = $venue->diningTables()->create(['code' => '1', 'qr_token' => 'stock-token']);

        $this->postJson(route('public.qr-order', $table->qr_token), ['items' => [['product_id' => $product->id, 'quantity' => 2]]])->assertCreated();
        $this->assertEquals(2, (float) $product->fresh()->stock_reserved);
        $this->assertEquals(200, (float) $ingredient->fresh()->stock_reserved);
        $this->postJson(route('public.qr-order', $table->qr_token), ['items' => [['product_id' => $product->id, 'quantity' => 1]]])->assertUnprocessable();
    }

    public function test_consumption_is_idempotent(): void
    {
        [, $venue] = $this->venue('consume');
        $category = ProductCategory::create(['venue_id' => $venue->id, 'name' => 'Pratos']);
        $product = Product::create(['venue_id' => $venue->id, 'product_category_id' => $category->id, 'name' => 'Prato', 'price' => 10, 'track_stock' => true, 'stock_on_hand' => 5]);
        $order = Order::create(['venue_id' => $venue->id, 'status' => 'pending', 'subtotal' => 20, 'total' => 20]);
        $order->items()->create(['product_id' => $product->id, 'name_snapshot' => 'Prato', 'unit_price' => 10, 'quantity' => 2, 'total' => 20]);
        $service = app(InventoryService::class);
        $service->reserve($order);
        $service->consume($order);
        $service->consume($order);
        $this->assertEquals(3, (float) $product->fresh()->stock_on_hand);
        $this->assertEquals(0, (float) $product->fresh()->stock_reserved);
    }

    public function test_geofence_blocks_outside_and_accepts_inside_coordinates(): void
    {
        [, $venue] = $this->venue('geo');
        $venue->update(['geofence_enabled' => true, 'latitude' => -19.9167, 'longitude' => -43.9345, 'geofence_radius_meters' => 100]);
        $category = ProductCategory::create(['venue_id' => $venue->id, 'name' => 'Itens']);
        $product = Product::create(['venue_id' => $venue->id, 'product_category_id' => $category->id, 'name' => 'Item', 'price' => 10]);
        $table = $venue->diningTables()->create(['code' => '1', 'qr_token' => 'geo-token']);
        $items = ['items' => [['product_id' => $product->id, 'quantity' => 1]]];

        $this->postJson(route('public.qr-order', $table->qr_token), $items + ['latitude' => -19.92, 'longitude' => -43.94])->assertForbidden();
        $this->postJson(route('public.qr-order', $table->qr_token), $items + ['latitude' => -19.9167, 'longitude' => -43.9345])->assertCreated();
    }

    public function test_whatsapp_notification_is_idempotent_and_sent_by_the_tenant_instance(): void
    {
        config(['services.evolution.url' => 'https://evolution.test', 'services.evolution.key' => 'key']);
        Http::fake(['https://evolution.test/message/sendText/*' => Http::response(['key' => ['id' => 'message']])]);
        [$tenant, $venue] = $this->venue('notify');
        EvolutionInstance::create(['tenant_id' => $tenant->id, 'instance_name' => 'cdbh-notify', 'status' => 'connected']);
        $order = Order::create(['venue_id' => $venue->id, 'status' => 'pending', 'customer_name' => 'Maria', 'customer_phone' => '(31) 99999-9999', 'whatsapp_consent' => true, 'subtotal' => 10, 'total' => 10]);

        app(WhatsappNotifier::class)->queue($order, 'order_received');
        app(WhatsappNotifier::class)->queue($order, 'order_received');

        $this->assertDatabaseCount('outbound_messages', 1);
        $this->assertDatabaseHas('outbound_messages', ['tenant_id' => $tenant->id, 'order_id' => $order->id, 'status' => 'sent']);
        Http::assertSentCount(1);
        Http::assertSent(fn ($request) => str_ends_with($request->url(), '/message/sendText/cdbh-notify') && $request['number'] === '5531999999999');
    }

    private function venue(string $slug): array
    {
        $tenant = Tenant::create(['name' => $slug, 'slug' => $slug]);
        $venue = Venue::create(['tenant_id' => $tenant->id, 'name' => $slug, 'subdomain' => $slug]);
        return [$tenant, $venue];
    }
}
