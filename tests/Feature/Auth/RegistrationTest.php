<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Tenant;
use App\Models\Venue;
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
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertAuthenticated();
        $this->assertDatabaseHas('tenants', ['name' => 'Restaurante Teste']);
        $this->assertDatabaseHas('venues', ['subdomain' => 'restaurante-teste']);
        $response->assertRedirect(route('dashboard', absolute: false));
    }
}
