<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Venue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KitchenDisplayTest extends TestCase
{
    use RefreshDatabase;

    public function test_venue_owner_can_view_and_advance_a_kitchen_item(): void
    {
        $tenant = Tenant::create(['name' => 'Bistrô', 'slug' => 'bistro']);
        $venue = Venue::create(['tenant_id' => $tenant->id, 'name' => 'Bistrô', 'subdomain' => 'localhost']);
        $user = User::factory()->create(['tenant_id' => $tenant->id, 'role' => 'owner']);
        $order = Order::create(['venue_id' => $venue->id, 'channel' => 'table', 'status' => 'pending', 'subtotal' => 20, 'total' => 20]);
        $item = OrderItem::create(['order_id' => $order->id, 'name_snapshot' => 'Hambúrguer', 'unit_price' => 20, 'quantity' => 1, 'total' => 20, 'station' => 'kitchen', 'status' => 'pending']);

        $this->actingAs($user)->get('/cozinha')->assertOk()->assertSee('Hambúrguer');
        $this->actingAs($user)->patch('/cozinha/itens/'.$item->id, ['status' => 'preparing'])->assertRedirect();

        $this->assertDatabaseHas(OrderItem::class, ['id' => $item->id, 'status' => 'preparing']);
        $this->assertDatabaseHas(Order::class, ['id' => $order->id, 'status' => 'preparing']);
    }
}
