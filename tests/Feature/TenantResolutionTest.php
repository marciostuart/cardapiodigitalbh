<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Tenant;
use App\Models\Venue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantResolutionTest extends TestCase
{
    use RefreshDatabase;

    public function test_tenant_root_domain_displays_its_public_menu(): void
    {
        $tenant = Tenant::create(['name' => 'Casa Teste', 'slug' => 'casa-teste']);
        $venue = Venue::create(['tenant_id' => $tenant->id, 'name' => 'Casa Teste', 'subdomain' => 'casa-teste']);
        $category = ProductCategory::create(['venue_id' => $venue->id, 'name' => 'Pratos']);
        Product::create(['venue_id' => $venue->id, 'product_category_id' => $category->id, 'name' => 'Executivo', 'price' => 24.90]);

        $this->get('http://casa-teste.cardapiodigitalbh.com.br/')
            ->assertOk()
            ->assertSee('Casa Teste')
            ->assertSee('Executivo');
    }

    public function test_legacy_tenant_menu_redirects_to_tenant_root(): void
    {
        $tenant = Tenant::create(['name' => 'Casa Teste', 'slug' => 'casa-teste']);
        Venue::create(['tenant_id' => $tenant->id, 'name' => 'Casa Teste', 'subdomain' => 'casa-teste']);

        $this->get('http://casa-teste.cardapiodigitalbh.com.br/menu')
            ->assertRedirect('http://casa-teste.cardapiodigitalbh.com.br');
    }

    public function test_suspended_tenant_public_menu_is_not_available(): void
    {
        $tenant = Tenant::create(['name' => 'Casa Suspensa', 'slug' => 'casa-suspensa', 'status' => 'suspended']);
        Venue::create(['tenant_id' => $tenant->id, 'name' => 'Casa Suspensa', 'subdomain' => 'casa-suspensa']);

        $this->get('http://casa-suspensa.cardapiodigitalbh.com.br/')->assertNotFound();
    }
}
