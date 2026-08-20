<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductOption;
use App\Models\ProductOptionGroup;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Venue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductOptionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_server_applies_additional_price_and_snapshot(): void
    {
        [$tenant, $venue, $product] = $this->catalog('options');
        $group = ProductOptionGroup::create(['product_id' => $product->id, 'name' => 'Tamanho', 'minimum' => 1, 'maximum' => 1]);
        $large = ProductOption::create(['product_option_group_id' => $group->id, 'name' => 'Grande', 'price' => 8]);

        $response = $this->postJson('http://options.cardapiodigitalbh.com.br/pedidos', [
            'channel' => 'pickup', 'customer_name' => 'Ana', 'customer_phone' => '31999999999', 'payment_method' => 'pix',
            'items' => [['product_id' => $product->id, 'quantity' => 2, 'option_ids' => [$large->id]]],
        ])->assertCreated();

        $response->assertJsonPath('order.total', '66.00');
        $this->assertDatabaseHas('order_items', ['product_id' => $product->id, 'unit_price' => 33, 'total' => 66]);
        $this->assertSame('Grande', $response->json('order.items.0.options_snapshot.0.name'));
    }

    public function test_required_group_and_cross_tenant_option_are_rejected(): void
    {
        [, , $productA] = $this->catalog('option-a');
        [, , $productB] = $this->catalog('option-b');
        $groupA = ProductOptionGroup::create(['product_id' => $productA->id, 'name' => 'Ponto', 'minimum' => 1, 'maximum' => 1]);
        $groupB = ProductOptionGroup::create(['product_id' => $productB->id, 'name' => 'Outro', 'minimum' => 0, 'maximum' => 1]);
        $foreign = ProductOption::create(['product_option_group_id' => $groupB->id, 'name' => 'Indevida', 'price' => 1]);
        $base = ['channel' => 'pickup', 'customer_name' => 'Ana', 'customer_phone' => '31999999999', 'payment_method' => 'pix'];

        $this->postJson('http://option-a.cardapiodigitalbh.com.br/pedidos', $base + ['items' => [['product_id' => $productA->id, 'quantity' => 1]]])->assertUnprocessable();
        $this->postJson('http://option-a.cardapiodigitalbh.com.br/pedidos', $base + ['items' => [['product_id' => $productA->id, 'quantity' => 1, 'option_ids' => [$foreign->id]]]])->assertUnprocessable();
    }

    public function test_owner_cannot_manage_another_tenant_option_group(): void
    {
        [$tenantA] = $this->catalog('owner-option-a');
        [, , $productB] = $this->catalog('owner-option-b');
        $groupB = ProductOptionGroup::create(['product_id' => $productB->id, 'name' => 'Molho', 'minimum' => 0, 'maximum' => 2]);
        $ownerA = User::factory()->create(['tenant_id' => $tenantA->id, 'role' => 'owner']);

        $this->actingAs($ownerA)->patch(route('owner.option-groups.update', $groupB), ['name' => 'Ataque', 'minimum' => 0, 'maximum' => 1, 'active' => 1])->assertNotFound();
    }

    private function catalog(string $slug): array
    {
        $tenant = Tenant::create(['name' => $slug, 'slug' => $slug]);
        $venue = Venue::create(['tenant_id' => $tenant->id, 'name' => $slug, 'subdomain' => $slug, 'pickup_enabled' => true]);
        $category = ProductCategory::create(['venue_id' => $venue->id, 'name' => 'Pratos']);
        $product = Product::create(['venue_id' => $venue->id, 'product_category_id' => $category->id, 'name' => 'Executivo', 'price' => 25]);
        return [$tenant, $venue, $product];
    }
}
