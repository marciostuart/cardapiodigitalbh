<?php

namespace Tests\Feature;

use App\Models\DiningTable;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Tab;
use App\Models\Tenant;
use App\Models\Venue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TableOrderTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A basic feature test example.
     */
    public function test_table_order_is_calculated_on_the_server(): void
    {
        $tenant = Tenant::create(['name' => 'Teste', 'slug' => 'teste']);
        $venue = Venue::create(['tenant_id' => $tenant->id, 'name' => 'Teste', 'subdomain' => 'localhost']);
        $category = ProductCategory::create(['venue_id' => $venue->id, 'name' => 'Bebidas']);
        $product = Product::create(['venue_id' => $venue->id, 'product_category_id' => $category->id, 'name' => 'Água', 'price' => 5]);
        $table = DiningTable::create(['venue_id' => $venue->id, 'code' => '01', 'qr_token' => fake()->uuid]);
        $response = $this->postJson('/tables/'.$table->id.'/orders', ['items' => [['product_id' => $product->id, 'quantity' => 2]]]);
        $response->assertCreated()->assertJsonPath('total', '10.00');
    }

    public function test_qr_menu_can_create_an_order_without_the_tenant_subdomain(): void
    {
        $tenant = Tenant::create(['name' => 'Teste', 'slug' => 'teste']);
        $venue = Venue::create(['tenant_id' => $tenant->id, 'name' => 'Teste', 'subdomain' => 'teste']);
        $category = ProductCategory::create(['venue_id' => $venue->id, 'name' => 'Lanches']);
        $product = Product::create(['venue_id' => $venue->id, 'product_category_id' => $category->id, 'name' => 'X-Salada', 'price' => 20]);
        $table = DiningTable::create(['venue_id' => $venue->id, 'code' => '02', 'qr_token' => fake()->uuid]);

        $this->get('/m/'.$table->qr_token)->assertOk()->assertSee('X-Salada');
        $this->postJson('/m/'.$table->qr_token.'/orders', [
            'items' => [['product_id' => $product->id, 'quantity' => 1]],
        ])->assertCreated()->assertJsonPath('total', '20.00');
    }

    public function test_customer_names_create_separate_individual_tabs_at_the_same_table(): void
    {
        $tenant = Tenant::create(['name' => 'Teste', 'slug' => 'teste']);
        $venue = Venue::create(['tenant_id' => $tenant->id, 'name' => 'Teste', 'subdomain' => 'teste']);
        $category = ProductCategory::create(['venue_id' => $venue->id, 'name' => 'Lanches']);
        $product = Product::create(['venue_id' => $venue->id, 'product_category_id' => $category->id, 'name' => 'X-Salada', 'price' => 20]);
        $table = DiningTable::create(['venue_id' => $venue->id, 'code' => '02', 'qr_token' => fake()->uuid]);
        $items = [['product_id' => $product->id, 'quantity' => 1]];

        $this->postJson('/m/'.$table->qr_token.'/orders', ['customer_name' => 'Ana', 'notes' => 'Sem cebola', 'items' => $items])->assertCreated();
        $this->postJson('/m/'.$table->qr_token.'/orders', ['customer_name' => 'Bruno', 'items' => $items])->assertCreated();

        $this->assertSame(2, Tab::where('dining_table_id', $table->id)->where('status', 'open')->count());
        $this->assertDatabaseHas(Tab::class, ['dining_table_id' => $table->id, 'customer_name' => 'Ana']);
        $this->assertDatabaseHas('orders', ['notes' => 'Sem cebola']);
    }

    public function test_suspended_tenant_cannot_receive_qr_orders(): void
    {
        $tenant = Tenant::create(['name' => 'Suspenso', 'slug' => 'suspenso', 'status' => 'suspended']);
        $venue = Venue::create(['tenant_id' => $tenant->id, 'name' => 'Suspenso', 'subdomain' => 'suspenso']);
        $category = ProductCategory::create(['venue_id' => $venue->id, 'name' => 'Lanches']);
        $product = Product::create(['venue_id' => $venue->id, 'product_category_id' => $category->id, 'name' => 'X-Salada', 'price' => 20]);
        $table = DiningTable::create(['venue_id' => $venue->id, 'code' => '02', 'qr_token' => fake()->uuid]);

        $this->get('/m/'.$table->qr_token)->assertNotFound();
        $this->postJson('/m/'.$table->qr_token.'/orders', [
            'items' => [['product_id' => $product->id, 'quantity' => 1]],
        ])->assertNotFound();
    }
}
