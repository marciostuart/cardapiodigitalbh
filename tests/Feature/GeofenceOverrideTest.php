<?php

namespace Tests\Feature;

use App\Models\DiningTable;
use App\Models\GeofenceOverride;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Venue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GeofenceOverrideTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_override_is_hashed_audited_and_usable_only_once(): void
    {
        $this->travelTo(now()->startOfSecond());
        [$tenant, , $table, $product] = $this->fixture('localhost');
        $owner = User::factory()->create(['tenant_id' => $tenant->id, 'role' => 'owner']);

        $this->actingAs($owner)
            ->post(route('owner.tables.geofence-override', $table))
            ->assertRedirect();

        $code = (string) session('geofence_access_code');
        $override = GeofenceOverride::sole();
        $this->assertMatchesRegularExpression('/^\d{6}$/', $code);
        $this->assertSame(hash_hmac('sha256', $table->id.'|'.$code, (string) config('app.key')), $override->code_hash);
        $this->assertNotSame($code, $override->code_hash);
        $this->assertTrue($override->expires_at->equalTo(now()->addMinutes(15)));
        $this->assertDatabaseHas('audit_logs', ['action' => 'geofence.override_created', 'subject_id' => $override->id]);

        $orderData = ['items' => [['product_id' => $product->id, 'quantity' => 1]], 'geofence_access_code' => $code];
        $this->postJson(route('public.qr-order', $table->qr_token), $orderData)->assertCreated();
        $this->assertNotNull($override->fresh()->used_at);
        $this->assertDatabaseHas('audit_logs', ['action' => 'geofence.override_used', 'subject_id' => $override->id]);
        $this->postJson(route('public.qr-order', $table->qr_token), $orderData)->assertForbidden();
    }

    public function test_expired_override_cannot_create_a_table_order(): void
    {
        [$tenant, , $table, $product] = $this->fixture('localhost');
        $owner = User::factory()->create(['tenant_id' => $tenant->id, 'role' => 'owner']);
        $this->actingAs($owner)->post(route('owner.tables.geofence-override', $table))->assertRedirect();
        $code = (string) session('geofence_access_code');
        $this->travel(16)->minutes();

        $this->postJson(route('public.qr-order', $table->qr_token), [
            'items' => [['product_id' => $product->id, 'quantity' => 1]],
            'geofence_access_code' => $code,
        ])->assertForbidden();

        $this->assertNull(GeofenceOverride::sole()->used_at);
        $this->assertDatabaseCount('orders', 0);
    }

    private function fixture(string $subdomain): array
    {
        $tenant = Tenant::create(['name' => 'Geofence', 'slug' => 'geofence-'.$subdomain]);
        $venue = Venue::create([
            'tenant_id' => $tenant->id,
            'name' => 'Geofence',
            'subdomain' => $subdomain,
            'geofence_enabled' => true,
            'latitude' => -19.9167,
            'longitude' => -43.9345,
            'geofence_radius_meters' => 100,
        ]);
        $table = DiningTable::create(['venue_id' => $venue->id, 'code' => '1', 'qr_token' => 'geo-'.$tenant->id]);
        $category = ProductCategory::create(['venue_id' => $venue->id, 'name' => 'Itens']);
        $product = Product::create(['venue_id' => $venue->id, 'product_category_id' => $category->id, 'name' => 'Item', 'price' => 10]);

        return [$tenant, $venue, $table, $product];
    }
}
