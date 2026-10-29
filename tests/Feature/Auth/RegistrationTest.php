<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Notifications\WelcomeToCardapio;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
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
        Notification::fake();

        $response = $this->post('/register', [
            'name' => 'Test User',
            'venue_name' => 'Restaurante Teste',
            'subdomain' => 'restaurante-teste',
            'email' => 'test@example.com',
            'password' => 'Password1',
            'password_confirmation' => 'Password1',
            'accept_terms' => '1',
        ]);

        $this->assertAuthenticated();
        $this->assertDatabaseHas('tenants', ['name' => 'Restaurante Teste', 'plan' => 'free', 'status' => 'active']);
        $this->assertDatabaseHas('venues', ['subdomain' => 'restaurante-teste']);
        $this->assertDatabaseHas('terms_acceptances', [
            'user_id' => User::where('email', 'test@example.com')->firstOrFail()->id,
            'terms_version' => config('legal.terms_version'),
            'privacy_version' => config('legal.privacy_version'),
        ]);
        Notification::assertSentTo(User::where('email', 'test@example.com')->firstOrFail(), WelcomeToCardapio::class);
        Notification::assertSentTo(User::where('email', 'test@example.com')->firstOrFail(), WelcomeToCardapio::class, function ($notification, $channels) {
            $mail = $notification->toMail(User::where('email', 'test@example.com')->firstOrFail());

            $this->assertSame('Seu Cardápio Digital BH está pronto', $mail->subject);
            $this->assertStringContainsString('Restaurante Teste', view($mail->view['html'], $mail->viewData)->render());

            return $channels === ['mail'];
        });
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

    public function test_registration_requires_terms_acceptance_before_creating_an_account(): void
    {
        $this->post('/register', [
            'name' => 'Test User',
            'venue_name' => 'Restaurante Teste',
            'subdomain' => 'restaurante-teste',
            'email' => 'test@example.com',
            'password' => 'Password1',
            'password_confirmation' => 'Password1',
        ])->assertSessionHasErrors('accept_terms');

        $this->assertDatabaseCount('tenants', 0);
        $this->assertDatabaseCount('users', 0);
    }
}
