<?php

namespace Tests\Feature;

use App\Models\EmailTemplate;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Venue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminPlatformTest extends TestCase
{
    use RefreshDatabase;

    public function test_platform_owner_can_change_plan_and_suspend_a_tenant(): void
    {
        $platformOwner = User::factory()->create(['role' => 'platform_owner']);
        $tenant = Tenant::create(['name' => 'Restaurante', 'slug' => 'restaurante']);
        Venue::create(['tenant_id' => $tenant->id, 'name' => 'Restaurante', 'subdomain' => 'restaurante']);

        $this->actingAs($platformOwner)
            ->patch(route('admin.plan.update', $tenant), ['plan' => 'pro', 'status' => 'suspended'])
            ->assertRedirect();

        $this->assertDatabaseHas(Tenant::class, ['id' => $tenant->id, 'plan' => 'pro', 'status' => 'suspended']);
    }

    public function test_platform_owner_can_impersonate_and_return_to_admin(): void
    {
        $platformOwner = User::factory()->create(['role' => 'platform_owner']);
        $tenant = Tenant::create(['name' => 'Restaurante', 'slug' => 'restaurante']);
        Venue::create(['tenant_id' => $tenant->id, 'name' => 'Restaurante', 'subdomain' => 'restaurante']);
        $customerOwner = User::factory()->create(['tenant_id' => $tenant->id, 'role' => 'owner']);

        $this->actingAs($platformOwner)
            ->post(route('admin.impersonate', $tenant))
            ->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($customerOwner);

        $this->post(route('admin.leave'))->assertRedirect(route('admin.platform'));
        $this->assertAuthenticatedAs($platformOwner);
    }

    public function test_customer_cannot_access_platform_admin(): void
    {
        $customer = User::factory()->create(['role' => 'owner']);

        $this->actingAs($customer)->get(route('admin.platform'))->assertForbidden();
    }

    public function test_platform_owner_can_edit_transactional_email_templates(): void
    {
        $platformOwner = User::factory()->create(['role' => 'platform_owner']);
        $template = EmailTemplate::where('key', 'welcome')->firstOrFail();

        $this->actingAs($platformOwner)->get(route('admin.email-templates.index'))
            ->assertOk()
            ->assertSee('Boas-vindas');

        $this->patch(route('admin.email-templates.update', $template), [
            'subject' => 'Bem-vindo à plataforma, {{name}}',
            'heading' => 'Tudo pronto',
            'body' => 'Seu estabelecimento {{venue_name}} está pronto.',
            'action_text' => 'Abrir painel',
            'footer' => 'Equipe Cardápio Digital BH',
            'active' => true,
        ])->assertRedirect();

        $this->assertDatabaseHas(EmailTemplate::class, [
            'id' => $template->id,
            'subject' => 'Bem-vindo à plataforma, {{name}}',
        ]);
    }

    public function test_customer_cannot_edit_transactional_email_templates(): void
    {
        $customer = User::factory()->create(['role' => 'owner']);

        $this->actingAs($customer)->get(route('admin.email-templates.index'))->assertForbidden();
    }
}
