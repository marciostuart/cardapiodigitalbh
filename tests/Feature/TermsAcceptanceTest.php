<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TermsAcceptanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_existing_user_must_accept_current_terms_before_opening_dashboard(): void
    {
        $user = User::factory()->withoutCurrentTermsAcceptance()->create();

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertRedirect(route('legal.acceptance.show'));

        $this->actingAs($user)
            ->get(route('legal.acceptance.show'))
            ->assertOk()
            ->assertSee('Termos de Uso')
            ->assertSee('Política de Privacidade');
    }

    public function test_current_terms_acceptance_is_recorded_once_with_version_and_timestamp(): void
    {
        $user = User::factory()->withoutCurrentTermsAcceptance()->create();

        $this->actingAs($user)
            ->post(route('legal.acceptance.store'), ['accept_terms' => '1'])
            ->assertRedirect(route('dashboard'));

        $this->assertDatabaseHas('terms_acceptances', [
            'user_id' => $user->id,
            'terms_version' => config('legal.terms_version'),
            'privacy_version' => config('legal.privacy_version'),
        ]);
        $this->assertTrue($user->fresh()->hasAcceptedCurrentLegalDocuments());

        $this->actingAs($user)
            ->post(route('legal.acceptance.store'), ['accept_terms' => '1'])
            ->assertRedirect(route('dashboard'));

        $this->assertDatabaseCount('terms_acceptances', 1);
    }

    public function test_existing_user_cannot_accept_without_checking_the_box(): void
    {
        $user = User::factory()->withoutCurrentTermsAcceptance()->create();

        $this->actingAs($user)
            ->post(route('legal.acceptance.store'), ['accept_terms' => '0'])
            ->assertSessionHasErrors('accept_terms');

        $this->assertDatabaseCount('terms_acceptances', 0);
    }

    public function test_document_version_change_requires_a_new_acceptance(): void
    {
        $user = User::factory()->create();
        config(['legal.privacy_version' => '2026-11-01']);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertRedirect(route('legal.acceptance.show'));

        $this->actingAs($user)
            ->post(route('legal.acceptance.store'), ['accept_terms' => '1'])
            ->assertRedirect(route('dashboard'));

        $this->assertDatabaseCount('terms_acceptances', 2);
    }
}
