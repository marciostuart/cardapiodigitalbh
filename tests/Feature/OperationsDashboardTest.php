<?php

namespace Tests\Feature;

use App\Models\DiningTable;
use App\Models\Tenant;
use App\Models\Venue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OperationsDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_venue_can_view_its_operations_dashboard(): void
    {
        $tenant = Tenant::create(['name' => 'Bistro Central', 'slug' => 'bistro-central']);
        $venue = Venue::create([
            'tenant_id' => $tenant->id,
            'name' => 'Bistro Central',
            'subdomain' => 'bistro',
        ]);
        DiningTable::create([
            'venue_id' => $venue->id,
            'code' => '12',
            'qr_token' => (string) str()->uuid(),
        ]);

        $this->get('/operacao', ['HOST' => 'bistro.localhost'])
            ->assertOk()
            ->assertSee('Bistro Central')
            ->assertSee('Mesa 12')
            ->assertSee('Vendas de hoje');
    }
}
