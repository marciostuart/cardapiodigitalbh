<?php

namespace Tests\Feature;

use App\Models\DiningTable;
use App\Models\Product;
use App\Models\ProductCategory;
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
        $tenant = Tenant::create(['name'=>'Teste','slug'=>'teste']);
        $venue = Venue::create(['tenant_id'=>$tenant->id,'name'=>'Teste','subdomain'=>'teste']);
        $category = ProductCategory::create(['venue_id'=>$venue->id,'name'=>'Bebidas']);
        $product = Product::create(['venue_id'=>$venue->id,'product_category_id'=>$category->id,'name'=>'Água','price'=>5]);
        $table = DiningTable::create(['venue_id'=>$venue->id,'code'=>'01','qr_token'=>fake()->uuid]);
        $response = $this->withHeader('Host','teste.cardapiodigitalbh.com.br')->postJson('/tables/'.$table->id.'/orders',['items'=>[['product_id'=>$product->id,'quantity'=>2]]]);
        $response->assertCreated()->assertJsonPath('total','10.00');
    }
}
