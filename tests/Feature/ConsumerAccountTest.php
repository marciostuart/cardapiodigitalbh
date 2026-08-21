<?php

namespace Tests\Feature;

use App\Models\ConsumerAccount;
use App\Models\DiningTable;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Tenant;
use App\Models\Venue;
use App\Notifications\ConsumerResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class ConsumerAccountTest extends TestCase
{
    use RefreshDatabase;

    public function test_consumer_can_create_a_global_account_and_use_it_in_another_establishment(): void
    {
        [, $firstVenue, $firstProduct] = $this->catalog('primeiro-consumidor');
        [, $secondVenue, $secondProduct] = $this->catalog('segundo-consumidor');
        $payload = $this->registrationPayload();

        $this->postJson('http://primeiro-consumidor.cardapiodigitalbh.com.br/conta/cadastro', $payload)
            ->assertCreated()
            ->assertJsonPath('consumer.name', 'Ana Souza');

        $consumer = ConsumerAccount::where('email', $payload['email'])->firstOrFail();
        $this->assertAuthenticated('consumer');
        $this->assertDatabaseHas('consumer_addresses', ['consumer_account_id' => $consumer->id, 'postal_code' => '30110-000']);

        $this->actingAs($consumer, 'consumer')->postJson('http://segundo-consumidor.cardapiodigitalbh.com.br/pedidos', [
            'channel' => 'pickup', 'payment_method' => 'pix', 'items' => [['product_id' => $secondProduct->id, 'quantity' => 1]],
        ])->assertCreated();

        $this->assertDatabaseHas('orders', ['venue_id' => $secondVenue->id, 'consumer_account_id' => $consumer->id, 'customer_name' => 'Ana Souza', 'customer_phone' => '5531999999999']);
        $this->assertDatabaseHas('customers', ['venue_id' => $secondVenue->id, 'phone' => '5531999999999', 'email' => $payload['email']]);
        Auth::shouldUse('web');
        $this->get('/dashboard')->assertRedirect('/login');
    }

    public function test_consumer_can_login_by_phone_and_table_orders_keep_the_global_account(): void
    {
        [, $venue, $product] = $this->catalog('mesa-consumidor');
        $consumer = ConsumerAccount::create(['name' => 'Ana Souza', 'phone' => '5531999999999', 'email' => 'ana@example.com', 'password' => Hash::make('Senha123')]);
        $consumer->addresses()->create($this->address());
        $table = DiningTable::create(['venue_id' => $venue->id, 'code' => '10', 'qr_token' => fake()->uuid]);

        $this->postJson('http://mesa-consumidor.cardapiodigitalbh.com.br/conta/entrar', ['phone' => '(31) 99999-9999', 'password' => 'Senha123'])
            ->assertOk()
            ->assertJsonPath('consumer.phone', '5531999999999');

        $this->postJson('/m/'.$table->qr_token.'/orders', [
            'items' => [['product_id' => $product->id, 'quantity' => 1]],
        ])->assertCreated();

        $this->assertDatabaseHas('orders', ['venue_id' => $venue->id, 'consumer_account_id' => $consumer->id, 'customer_name' => 'Ana Souza']);
    }

    public function test_consumer_password_reset_does_not_reveal_account_existence(): void
    {
        [, $venue] = $this->catalog('senha-consumidor');
        $consumer = ConsumerAccount::create(['name' => 'Ana Souza', 'phone' => '5531999999999', 'email' => 'ana@example.com', 'password' => Hash::make('Senha123')]);
        Notification::fake();

        $this->postJson('http://senha-consumidor.cardapiodigitalbh.com.br/conta/esqueci-senha', ['email' => 'ausente@example.com'])
            ->assertOk()
            ->assertJsonPath('message', 'Se houver uma conta com este e-mail, enviaremos as instruções para redefinir a senha.');
        Notification::assertNothingSent();

        $this->postJson('http://senha-consumidor.cardapiodigitalbh.com.br/conta/esqueci-senha', ['email' => $consumer->email])->assertOk();
        Notification::assertSentTo($consumer, ConsumerResetPassword::class);

        $token = Password::broker('consumers')->createToken($consumer);
        $this->post('http://senha-consumidor.cardapiodigitalbh.com.br/conta/nova-senha', [
            'token' => $token, 'email' => $consumer->email, 'password' => 'NovaSenha123', 'password_confirmation' => 'NovaSenha123',
        ])->assertRedirect('/');
        $this->assertTrue(Hash::check('NovaSenha123', $consumer->fresh()->password));
    }

    private function registrationPayload(): array
    {
        return ['name' => 'Ana Souza', 'phone' => '(31) 99999-9999', 'email' => 'ana@example.com', 'password' => 'Senha123', 'password_confirmation' => 'Senha123'] + $this->address();
    }

    private function address(): array
    {
        return ['postal_code' => '30110-000', 'street' => 'Rua A', 'number' => '10', 'neighborhood' => 'Centro', 'city' => 'Belo Horizonte', 'state' => 'MG', 'complement' => null, 'reference' => null];
    }

    private function catalog(string $slug): array
    {
        $tenant = Tenant::create(['name' => $slug, 'slug' => $slug]);
        $venue = Venue::create(['tenant_id' => $tenant->id, 'name' => $slug, 'subdomain' => $slug]);
        $category = ProductCategory::create(['venue_id' => $venue->id, 'name' => 'Pratos']);
        $product = Product::create(['venue_id' => $venue->id, 'product_category_id' => $category->id, 'name' => 'Executivo', 'price' => 25]);
        return [$tenant, $venue, $product];
    }
}
