<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
    }

    public function test_new_users_can_register(): void
    {
        $response = $this->post('/register', [
            'name' => 'Test User',
            'venue_name' => 'Restaurante Teste',
            'subdomain' => 'restaurante-teste',
            'email' => 'test@example.com',
            'password' => 'Password1',
            'password_confirmation' => 'Password1',
        ]);

        $this->assertAuthenticated();
        $this->assertDatabaseHas('tenants', ['name' => 'Restaurante Teste', 'plan' => 'free', 'status' => 'active']);
        $this->assertDatabaseHas('venues', ['subdomain' => 'restaurante-teste']);
        $response->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_reserved_subdomain_cannot_be_registered(): void
    {
        $this->post('/register', [
            'name' => 'Test User',
            'venue_name' => 'Restaurante Teste',
            'subdomain' => 'admin',
            'email' => 'test@example.com',
            'password' => 'Password1',
            'password_confirmation' => 'Password1',
        ])->assertSessionHasErrors('subdomain');

        $this->assertGuest();
    }
}
