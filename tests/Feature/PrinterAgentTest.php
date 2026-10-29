<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\PrintJob;
use App\Models\ProductCategory;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Venue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PrinterAgentTest extends TestCase
{
    use RefreshDatabase;

    public function test_pairing_code_is_one_time_and_device_only_receives_its_tenant_jobs(): void
    {
        [$tenant, $venue] = $this->venue('printer-one');
        [$otherTenant, $otherVenue] = $this->venue('printer-two');
        $owner = User::factory()->create(['tenant_id' => $tenant->id, 'role' => 'owner']);

        $this->actingAs($owner)->post(route('owner.printers.pair'))->assertRedirect();
        $code = (string) session('printer_pairing_code');
        $this->assertSame(8, strlen($code));

        $paired = $this->postJson('/api/print-agent/pair', ['code' => $code, 'name' => 'Computador caixa'])
            ->assertCreated()->assertJsonPath('device.name', 'Computador caixa');
        $token = $paired->json('token');
        $device = \App\Models\PrinterAgentDevice::sole();
        $this->assertSame(hash_hmac('sha256', $token, config('app.key')), $device->token_hash);

        $this->postJson('/api/print-agent/pair', ['code' => $code, 'name' => 'Tentativa repetida'])->assertUnprocessable();

        $order = Order::create(['venue_id' => $venue->id, 'status' => 'accepted', 'subtotal' => 10, 'total' => 10]);
        $otherOrder = Order::create(['venue_id' => $otherVenue->id, 'status' => 'accepted', 'subtotal' => 20, 'total' => 20]);
        $job = PrintJob::create(['tenant_id' => $tenant->id, 'venue_id' => $venue->id, 'order_id' => $order->id, 'printer_agent_device_id' => $device->id, 'kind' => 'kitchen_ticket', 'job_key' => 'order:one', 'payload' => ['order_id' => $order->id], 'status' => 'queued']);
        PrintJob::create(['tenant_id' => $otherTenant->id, 'venue_id' => $otherVenue->id, 'order_id' => $otherOrder->id, 'kind' => 'kitchen_ticket', 'job_key' => 'order:other', 'payload' => ['order_id' => $otherOrder->id], 'status' => 'queued']);

        $this->withToken($token)->getJson('/api/print-agent/jobs')->assertOk()->assertJsonCount(1, 'jobs')->assertJsonPath('jobs.0.payload.order_id', $order->id);
        $this->assertDatabaseHas('print_jobs', ['id' => $job->id, 'status' => 'printing', 'printer_agent_device_id' => $device->id]);

        $this->post(route('owner.printers.reprint', $order))->assertRedirect();
        $this->assertDatabaseHas('print_jobs', ['order_id' => $order->id, 'kind' => 'kitchen_ticket_reprint', 'status' => 'queued']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'printer.kitchen_ticket_reprinted', 'subject_id' => $order->id]);
    }

    private function venue(string $slug): array
    {
        $tenant = Tenant::create(['name' => $slug, 'slug' => $slug]);
        $venue = Venue::create(['tenant_id' => $tenant->id, 'name' => $slug, 'subdomain' => $slug === 'printer-one' ? 'localhost' : $slug]);
        ProductCategory::create(['venue_id' => $venue->id, 'name' => 'Pratos']);

        return [$tenant, $venue];
    }
}
