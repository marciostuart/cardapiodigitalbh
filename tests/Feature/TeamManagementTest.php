<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use App\Models\Venue;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class TeamManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_create_a_waiter_with_scoped_access(): void
    {
        Notification::fake();
        [$owner, $tenant] = $this->owner();

        $this->actingAs($owner)->post('/painel/equipe', [
            'name' => 'Ana Garçonete',
            'email' => 'ana@example.com',
            'role' => 'waiter',
            'password' => 'Password1',
            'password_confirmation' => 'Password1',
        ])->assertRedirect();

        $waiter = User::where('email', 'ana@example.com')->sole();
        $this->assertSame($tenant->id, $waiter->tenant_id);
        $this->assertNull($waiter->email_verified_at);
        Notification::assertSentTo($waiter, VerifyEmail::class);
        $this->actingAs($waiter)->get('/operacao')->assertRedirect(route('verification.notice'));
        $waiter->markEmailAsVerified();
        $this->actingAs($waiter)->get('/dashboard')->assertRedirect(route('legal.acceptance.show'));
        $this->actingAs($waiter)->post(route('legal.acceptance.store'), ['accept_terms' => '1'])->assertRedirect(route('dashboard'));
        $this->actingAs($waiter)->get('/dashboard')->assertRedirect(route('operations.index'));
        $this->actingAs($waiter)->get('/operacao')->assertOk();
        $this->actingAs($waiter)->get('/painel/cardapio')->assertForbidden();
        $this->actingAs($waiter)->get('/caixa')->assertForbidden();
    }

    public function test_inactive_team_member_cannot_log_in(): void
    {
        [, $tenant] = $this->owner();
        User::factory()->create(['tenant_id' => $tenant->id, 'role' => 'kitchen', 'active' => false, 'email' => 'cozinha@example.com', 'password' => 'Password1']);

        $this->post('/login', ['email' => 'cozinha@example.com', 'password' => 'Password1'])->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    private function owner(): array
    {
        $tenant = Tenant::create(['name' => 'Bistrô', 'slug' => 'bistro']);
        Venue::create(['tenant_id' => $tenant->id, 'name' => 'Bistrô', 'subdomain' => 'localhost']);
        $owner = User::factory()->create(['tenant_id' => $tenant->id, 'role' => 'owner']);

        return [$owner, $tenant];
    }
}
