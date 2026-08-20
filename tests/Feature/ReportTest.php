<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Venue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_sees_only_own_sales_and_can_export_csv(): void
    {
        $tenant = Tenant::create(['name' => 'Casa', 'slug' => 'casa']);
        $venue = Venue::create(['tenant_id' => $tenant->id, 'name' => 'Casa', 'subdomain' => 'casa']);
        $owner = User::factory()->create(['tenant_id' => $tenant->id, 'role' => 'owner']);
        $order = Order::create(['venue_id' => $venue->id, 'status' => 'completed', 'channel' => 'pickup', 'subtotal' => 30, 'total' => 30]);
        $order->items()->create(['name_snapshot' => 'Prato', 'unit_price' => 15, 'quantity' => 2, 'total' => 30]);
        $otherTenant = Tenant::create(['name' => 'Outra', 'slug' => 'outra']);
        $otherVenue = Venue::create(['tenant_id' => $otherTenant->id, 'name' => 'Outra', 'subdomain' => 'outra']);
        Order::create(['venue_id' => $otherVenue->id, 'status' => 'completed', 'subtotal' => 999, 'total' => 999]);

        $this->actingAs($owner)->get(route('owner.reports.index'))->assertOk()->assertSee('R$ 30,00')->assertDontSee('999,00');
        $response = $this->actingAs($owner)->get(route('owner.reports.csv'));
        $response->assertOk()->assertDownload();
        $this->assertStringContainsString('pickup', $response->streamedContent());
    }
}
