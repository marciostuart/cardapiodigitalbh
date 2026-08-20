<?php

namespace Tests\Feature;

use App\Models\EvolutionInstance;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Venue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PlatformEnhancementsTest extends TestCase
{
    use RefreshDatabase;

    public function test_platform_owner_has_a_distinct_executive_dashboard(): void
    {
        $owner = User::factory()->create(['role' => 'platform_owner']);
        $this->actingAs($owner)->get(route('platform.dashboard'))->assertOk()->assertSee('Central de controle');
        $this->actingAs($owner)->get(route('admin.platform'))->assertOk()->assertSee('Estabelecimentos');
        $this->actingAs($owner)->get(route('dashboard'))->assertRedirect(route('platform.dashboard'));
    }

    public function test_popularity_is_the_default_and_is_calculated_inside_each_category(): void
    {
        [$tenant, $venue] = $this->tenantVenue('popular');
        $category = ProductCategory::create(['venue_id' => $venue->id, 'name' => 'Lanches']);
        $manualFirst = Product::create(['venue_id' => $venue->id, 'product_category_id' => $category->id, 'name' => 'Manual primeiro', 'price' => 10, 'position' => 1]);
        $popular = Product::create(['venue_id' => $venue->id, 'product_category_id' => $category->id, 'name' => 'Mais vendido', 'price' => 12, 'position' => 99]);
        $order = Order::create(['venue_id' => $venue->id, 'status' => 'completed', 'subtotal' => 36, 'total' => 36]);
        $order->items()->create(['product_id' => $popular->id, 'name_snapshot' => $popular->name, 'unit_price' => 12, 'quantity' => 3, 'total' => 36]);

        $response = $this->get('http://popular.cardapiodigitalbh.com.br/')->assertOk();
        $response->assertSeeInOrder(['Mais vendido', 'Manual primeiro']);
        $this->assertSame('popularity', $venue->fresh()->catalog_sort_mode);
    }

    public function test_manual_catalog_mode_respects_position(): void
    {
        [, $venue] = $this->tenantVenue('manual');
        $venue->update(['catalog_sort_mode' => 'manual']);
        $category = ProductCategory::create(['venue_id' => $venue->id, 'name' => 'Pratos']);
        Product::create(['venue_id' => $venue->id, 'product_category_id' => $category->id, 'name' => 'Segundo', 'price' => 10, 'position' => 20]);
        Product::create(['venue_id' => $venue->id, 'product_category_id' => $category->id, 'name' => 'Primeiro', 'price' => 10, 'position' => 10]);
        $this->get('http://manual.cardapiodigitalbh.com.br/')->assertOk()->assertSeeInOrder(['Primeiro', 'Segundo']);
    }

    public function test_reset_whats_deletes_only_the_selected_tenant_instance(): void
    {
        config(['services.evolution.url' => 'https://evolution.test', 'services.evolution.key' => 'secret-test']);
        Http::fake(['https://evolution.test/instance/delete/*' => Http::response(['status' => 'SUCCESS'])]);
        $platformOwner = User::factory()->create(['role' => 'platform_owner']);
        [$tenantA] = $this->tenantVenue('tenant-a');
        [$tenantB] = $this->tenantVenue('tenant-b');
        $instanceA = EvolutionInstance::create(['tenant_id' => $tenantA->id, 'instance_name' => 'cdbh-a', 'status' => 'degraded']);
        $instanceB = EvolutionInstance::create(['tenant_id' => $tenantB->id, 'instance_name' => 'cdbh-b', 'status' => 'connected']);

        $this->actingAs($platformOwner)->delete(route('admin.whatsapp.reset', $tenantA))->assertRedirect();

        $this->assertDatabaseMissing('evolution_instances', ['id' => $instanceA->id]);
        $this->assertDatabaseHas('evolution_instances', ['id' => $instanceB->id, 'instance_name' => 'cdbh-b']);
        $this->assertDatabaseHas('audit_logs', ['tenant_id' => $tenantA->id, 'action' => 'whatsapp.instance_reset']);
        Http::assertSentCount(1);
        Http::assertSent(fn ($request) => $request->method() === 'DELETE' && str_ends_with($request->url(), '/instance/delete/cdbh-a'));
    }

    public function test_tenant_owner_can_start_own_whatsapp_connection(): void
    {
        config(['services.evolution.url' => 'https://evolution.test', 'services.evolution.key' => 'secret-test']);
        Http::fake(['https://evolution.test/instance/create' => Http::response(['qrcode' => ['base64' => 'abc123']])]);
        [$tenant] = $this->tenantVenue('whatsapp');
        $owner = User::factory()->create(['tenant_id' => $tenant->id, 'role' => 'owner']);

        $this->actingAs($owner)->post(route('owner.whatsapp.connect'))->assertRedirect()->assertSessionHas('whatsapp_qr', 'abc123');
        $this->assertDatabaseHas('evolution_instances', ['tenant_id' => $tenant->id, 'status' => 'connecting']);
    }

    private function tenantVenue(string $slug): array
    {
        $tenant = Tenant::create(['name' => $slug, 'slug' => $slug]);
        $venue = Venue::create(['tenant_id' => $tenant->id, 'name' => $slug, 'subdomain' => $slug]);
        return [$tenant, $venue];
    }
}
