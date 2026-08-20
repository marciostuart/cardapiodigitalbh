<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use App\Models\Venue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VenueAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_from_another_tenant_cannot_access_operations(): void
    {
        $venueTenant = Tenant::create(['name' => 'Casa A', 'slug' => 'casa-a']);
        Venue::create(['tenant_id' => $venueTenant->id, 'name' => 'Casa A', 'subdomain' => 'localhost']);
        $otherTenant = Tenant::create(['name' => 'Casa B', 'slug' => 'casa-b']);
        $user = User::factory()->create(['tenant_id' => $otherTenant->id]);

        $this->actingAs($user)->get('/operacao')->assertForbidden();
    }
}
