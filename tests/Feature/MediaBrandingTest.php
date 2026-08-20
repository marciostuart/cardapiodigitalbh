<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use App\Models\Venue;
use App\Services\ImageOptimizer;
use App\Services\TenantMedia;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MediaBrandingTest extends TestCase
{
    use RefreshDatabase;

    public function test_media_uses_tenant_category_and_seo_filename(): void
    {
        Storage::fake('s3');
        config(['filesystems.media_disk' => 's3']);
        $tenant = Tenant::create(['name' => 'Pão & Sabor', 'slug' => 'pao-sabor']);
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=');
        $optimized = tempnam(sys_get_temp_dir(), 'optimized-');
        file_put_contents($optimized, 'webp-content');
        $this->mock(ImageOptimizer::class)->shouldReceive('toWebp')->once()->andReturn($optimized);
        $path = app(TenantMedia::class)->store($tenant, UploadedFile::fake()->createWithContent('foto.png', $png), 'products', 'Pão de Queijo Especial');
        $this->assertStringStartsWith("tenants/{$tenant->id}/products/pao-de-queijo-especial-", $path);
        $this->assertStringEndsWith('.webp', $path);
        Storage::disk('s3')->assertExists($path);
    }

    public function test_slug_change_requires_confirmation_and_availability(): void
    {
        [$user,$venue] = $this->owner('slug-atual');
        $payload = ['name' => 'Loja', 'timezone' => 'America/Sao_Paulo', 'subdomain' => 'slug-novo'];
        $this->actingAs($user)->patch(route('owner.settings.update'), $payload)->assertUnprocessable();
        $this->actingAs($user)->patch(route('owner.settings.update'), $payload + ['confirm_slug_change' => '1'])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame('slug-novo', $venue->fresh()->subdomain);
    }

    public function test_private_tenant_media_is_streamed_through_the_public_route(): void
    {
        $this->withoutExceptionHandling();
        Storage::fake('s3');
        config(['filesystems.media_disk' => 's3']);
        [, $venue] = $this->owner('midia-publica');
        $path = "tenants/{$venue->tenant_id}/products/produto-teste.webp";
        Storage::disk('s3')->put($path, 'webp-content');

        $this->get('http://midia-publica.cardapiodigitalbh.com.br/media/products/produto-teste.webp')
            ->assertOk()
            ->assertHeader('Content-Type', 'image/webp')
            ->assertStreamedContent('webp-content');
    }

    private function owner(string $slug): array
    {
        $tenant = Tenant::create(['name' => $slug, 'slug' => $slug]);
        $venue = Venue::create(['tenant_id' => $tenant->id, 'name' => $slug, 'subdomain' => $slug]);
        $user = User::factory()->create(['tenant_id' => $tenant->id, 'role' => 'owner']);

        return [$user, $venue];
    }
}
