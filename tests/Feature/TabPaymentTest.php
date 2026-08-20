<?php

namespace Tests\Feature;

use App\Models\DiningTable;
use App\Models\Order;
use App\Models\Tab;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Venue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TabPaymentTest extends TestCase
{
    use RefreshDatabase;

    public function test_full_payment_closes_tab_and_releases_table(): void
    {
        $tenant = Tenant::create(['name' => 'Bistrô', 'slug' => 'bistro']);
        $venue = Venue::create(['tenant_id' => $tenant->id, 'name' => 'Bistrô', 'subdomain' => 'localhost']);
        $user = User::factory()->create(['tenant_id' => $tenant->id, 'role' => 'owner']);
        $table = DiningTable::create(['venue_id' => $venue->id, 'code' => '1', 'status' => 'occupied', 'qr_token' => fake()->uuid]);
        $tab = Tab::create(['venue_id' => $venue->id, 'dining_table_id' => $table->id, 'code' => 'M1', 'status' => 'open', 'opened_at' => now()]);
        Order::create(['venue_id' => $venue->id, 'tab_id' => $tab->id, 'channel' => 'table', 'status' => 'ready', 'subtotal' => 30, 'total' => 30]);

        $this->actingAs($user)->get('/caixa')->assertOk()->assertSee('M1');
        $this->actingAs($user)->post('/tabs/'.$tab->id.'/payments', ['method' => 'pix_external', 'amount' => '30.00'])->assertRedirect();

        $this->assertDatabaseHas(Tab::class, ['id' => $tab->id, 'status' => 'closed']);
        $this->assertDatabaseHas(DiningTable::class, ['id' => $table->id, 'status' => 'available']);
    }

    public function test_payment_cannot_exceed_open_balance(): void
    {
        $tenant = Tenant::create(['name' => 'Bistrô', 'slug' => 'bistro']);
        $venue = Venue::create(['tenant_id' => $tenant->id, 'name' => 'Bistrô', 'subdomain' => 'localhost']);
        $user = User::factory()->create(['tenant_id' => $tenant->id, 'role' => 'owner']);
        $table = DiningTable::create(['venue_id' => $venue->id, 'code' => '1', 'status' => 'occupied', 'qr_token' => fake()->uuid]);
        $tab = Tab::create(['venue_id' => $venue->id, 'dining_table_id' => $table->id, 'code' => 'M1', 'status' => 'open', 'opened_at' => now()]);
        Order::create(['venue_id' => $venue->id, 'tab_id' => $tab->id, 'channel' => 'table', 'status' => 'ready', 'subtotal' => 30, 'total' => 30]);

        $this->actingAs($user)->postJson('/tabs/'.$tab->id.'/payments', ['method' => 'cash', 'amount' => '31.00'])->assertUnprocessable();
    }

    public function test_closing_an_individual_tab_keeps_table_occupied_while_another_tab_is_open(): void
    {
        $tenant = Tenant::create(['name' => 'Bistrô', 'slug' => 'bistro']);
        $venue = Venue::create(['tenant_id' => $tenant->id, 'name' => 'Bistrô', 'subdomain' => 'localhost']);
        $user = User::factory()->create(['tenant_id' => $tenant->id, 'role' => 'owner']);
        $table = DiningTable::create(['venue_id' => $venue->id, 'code' => '1', 'status' => 'occupied', 'qr_token' => fake()->uuid]);
        $paidTab = Tab::create(['venue_id' => $venue->id, 'dining_table_id' => $table->id, 'code' => 'M1-A', 'customer_name' => 'Ana', 'status' => 'open', 'opened_at' => now()]);
        Tab::create(['venue_id' => $venue->id, 'dining_table_id' => $table->id, 'code' => 'M1-B', 'customer_name' => 'Bruno', 'status' => 'open', 'opened_at' => now()]);
        Order::create(['venue_id' => $venue->id, 'tab_id' => $paidTab->id, 'channel' => 'table', 'status' => 'ready', 'subtotal' => 30, 'total' => 30]);

        $this->actingAs($user)->post('/tabs/'.$paidTab->id.'/payments', ['method' => 'pix_external', 'amount' => '30.00'])->assertRedirect();

        $this->assertDatabaseHas(DiningTable::class, ['id' => $table->id, 'status' => 'occupied']);
    }
}
