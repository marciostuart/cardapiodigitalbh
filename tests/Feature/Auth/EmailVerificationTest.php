<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class EmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_email_verification_screen_can_be_rendered(): void
    {
        $user = User::factory()->unverified()->create();

        $response = $this->actingAs($user)->get('/verify-email');

        $response->assertStatus(200);
    }

    public function test_email_can_be_verified(): void
    {
        $user = User::factory()->unverified()->create();

        Event::fake();

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1($user->email)]
        );

        $response = $this->actingAs($user)->get($verificationUrl);

        Event::assertDispatched(Verified::class);
        $this->assertTrue($user->fresh()->hasVerifiedEmail());
        $response->assertRedirect(route('dashboard', absolute: false).'?verified=1');
    }

    public function test_email_verification_link_works_behind_https_reverse_proxy(): void
    {
        $user = User::factory()->unverified()->create();

        Event::fake();
        URL::forceRootUrl('https://cardapiodigitalbh.com.br');
        URL::forceScheme('https');

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1($user->email)]
        );
        $verificationUri = parse_url($verificationUrl, PHP_URL_PATH).'?'.parse_url($verificationUrl, PHP_URL_QUERY);

        $response = $this->actingAs($user)
            ->withServerVariables([
                'HTTP_HOST' => 'cardapiodigitalbh.com.br',
                'SERVER_PORT' => 80,
                'HTTPS' => 'off',
                'REMOTE_ADDR' => '10.0.1.28',
                'HTTP_X_FORWARDED_HOST' => 'cardapiodigitalbh.com.br',
                'HTTP_X_FORWARDED_PORT' => '443',
                'HTTP_X_FORWARDED_PROTO' => 'https',
            ])
            ->get($verificationUri);

        Event::assertDispatched(Verified::class);
        $this->assertTrue($user->fresh()->hasVerifiedEmail());
        $response->assertRedirect(route('dashboard', absolute: false).'?verified=1');
    }

    public function test_verification_email_uses_canonical_url_without_default_https_port(): void
    {
        $user = User::factory()->unverified()->create();

        config(['app.url' => 'https://cardapiodigitalbh.com.br']);
        URL::forceRootUrl('http://cardapiodigitalbh.com.br:443');
        URL::forceScheme('https');

        try {
            $mail = (new VerifyEmail)->toMail($user);
            $verificationUrl = $mail->viewData['actionUrl'];

            $this->assertStringStartsWith('https://cardapiodigitalbh.com.br/verify-email/', $verificationUrl);
            $this->assertStringNotContainsString(':443/', $verificationUrl);
            $this->assertTrue(URL::hasValidSignature(Request::create($verificationUrl)));
        } finally {
            URL::forceRootUrl(null);
            URL::forceScheme(null);
        }
    }

    public function test_email_is_not_verified_with_invalid_hash(): void
    {
        $user = User::factory()->unverified()->create();

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1('wrong-email')]
        );

        $this->actingAs($user)->get($verificationUrl);

        $this->assertFalse($user->fresh()->hasVerifiedEmail());
    }
}
