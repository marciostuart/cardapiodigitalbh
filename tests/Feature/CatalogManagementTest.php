<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Venue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CatalogManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_open_and_manage_own_catalog(): void
    {
        [$user, $venue] = $this->ownerWithVenue('casa-teste');

        $this->actingAs($user)->get('/painel/cardapio')->assertOk()->assertSee('Cardápio');
        $this->actingAs($user)->post('/painel/categorias', ['name' => 'Hambúrgueres'])->assertRedirect();
        $category = ProductCategory::where('venue_id', $venue->id)->sole();

        $this->actingAs($user)->post('/painel/produtos', [
            'product_category_id' => $category->id,
            'name' => 'Clássico',
            'price' => '29.90',
            'station' => 'kitchen',
        ])->assertRedirect()->assertSessionHasNoErrors();

        $this->assertDatabaseHas(Product::class, ['venue_id' => $venue->id, 'name' => 'Clássico', 'price' => 29.90]);
    }

    public function test_owner_cannot_add_product_to_another_venue_category(): void
    {
        [$user] = $this->ownerWithVenue('casa-a');
        [, $otherVenue] = $this->ownerWithVenue('casa-b');
        $category = ProductCategory::create(['venue_id' => $otherVenue->id, 'name' => 'Privada']);

        $this->actingAs($user)->post('/painel/produtos', [
            'product_category_id' => $category->id,
            'name' => 'Produto indevido',
            'price' => 10,
        ])->assertUnprocessable();
    }

    public function test_owner_can_open_own_product_editor_but_not_another_tenants(): void
    {
        [$user, $venue] = $this->ownerWithVenue('editor-a');
        [, $otherVenue] = $this->ownerWithVenue('editor-b');
        $category = ProductCategory::create(['venue_id' => $venue->id, 'name' => 'Lanches']);
        $otherCategory = ProductCategory::create(['venue_id' => $otherVenue->id, 'name' => 'Privada']);
        $product = Product::create(['venue_id' => $venue->id, 'product_category_id' => $category->id, 'name' => 'X-Burger', 'price' => 20]);
        $otherProduct = Product::create(['venue_id' => $otherVenue->id, 'product_category_id' => $otherCategory->id, 'name' => 'Privado', 'price' => 30]);

        $this->actingAs($user)->get(route('owner.products.create'))->assertOk()->assertSee('Cadastrar produto');
        $this->actingAs($user)->get(route('owner.products.edit', $product))->assertOk()->assertSee('Editar produto')->assertSee('Variações e adicionais');
        $this->actingAs($user)->get(route('owner.products.edit', $otherProduct))->assertNotFound();
    }

    public function test_owner_can_render_a_scannable_table_qr_code(): void
    {
        [$user, $venue] = $this->ownerWithVenue('casa-qr');
        $table = $venue->diningTables()->create(['code' => '10', 'qr_token' => fake()->uuid]);

        $this->actingAs($user)->get(route('owner.tables.qr', $table))
            ->assertOk()
            ->assertHeader('Content-Type', 'image/svg+xml')
            ->assertSee('<svg', false);
    }

    public function test_owner_can_update_venue_identity_without_changing_the_public_slug(): void
    {
        [$user, $venue] = $this->ownerWithVenue('slug-permanente');

        $this->actingAs($user)->patch('/painel/configuracoes', [
            'name' => 'Novo Nome',
            'timezone' => 'America/Sao_Paulo',
        ])->assertRedirect()->assertSessionHasNoErrors();

        $this->assertDatabaseHas(Venue::class, ['id' => $venue->id, 'name' => 'Novo Nome', 'subdomain' => 'slug-permanente']);
    }

    private function ownerWithVenue(string $slug): array
    {
        $tenant = Tenant::create(['name' => $slug, 'slug' => $slug]);
        $venue = Venue::create(['tenant_id' => $tenant->id, 'name' => $slug, 'subdomain' => $slug]);
        $user = User::factory()->create(['tenant_id' => $tenant->id, 'role' => 'owner']);

        return [$user, $venue];
    }
}
