<?php

namespace App\Http\Controllers;

use App\Models\PrintJob;
use App\Models\AuditLog;
use App\Models\Order;
use App\Models\PrinterAgentDevice;
use App\Models\PrinterPairingCode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\ThermalPrintService;

class OwnerPrinterController extends Controller
{
    public function index(Request $request)
    {
        $tenant = $request->user()->tenant;
        $venue = $tenant->venues()->firstOrFail();
        $devices = PrinterAgentDevice::where('tenant_id', $tenant->id)->orderByDesc('is_primary')->orderBy('name')->get();
        $jobs = PrintJob::where('tenant_id', $tenant->id)->latest()->limit(20)->get();

        return view('owner.printers', compact('devices', 'jobs'));
    }

    public function pair(Request $request)
    {
        $tenant = $request->user()->tenant;
        $venue = $tenant->venues()->firstOrFail();
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        $code = '';
        for ($index = 0; $index < 8; $index++) {
            $code .= $alphabet[random_int(0, strlen($alphabet) - 1)];
        }
        $expiresAt = now()->addMinutes(10);

        DB::transaction(function () use ($request, $tenant, $venue, $code, $expiresAt): void {
            PrinterPairingCode::where('venue_id', $venue->id)->whereNull('used_at')->delete();
            PrinterPairingCode::create([
                'tenant_id' => $tenant->id,
                'venue_id' => $venue->id,
                'created_by' => $request->user()->id,
                'code_hash' => hash_hmac('sha256', $code, (string) config('app.key')),
                'expires_at' => $expiresAt,
            ]);
        });

        return redirect()->route('owner.printers.index')
            ->with('printer_pairing_code', $code)
            ->with('printer_pairing_expires', $expiresAt->format('d/m/Y H:i'));
    }

    public function makePrimary(Request $request, PrinterAgentDevice $device)
    {
        abort_unless($device->tenant_id === $request->user()->tenant_id && $device->active, 404);

        DB::transaction(function () use ($request, $device): void {
            PrinterAgentDevice::where('tenant_id', $request->user()->tenant_id)->update(['is_primary' => false]);
            $device->update(['is_primary' => true]);
            PrintJob::where('tenant_id', $device->tenant_id)->where('status', 'queued')->update([
                'printer_agent_device_id' => $device->id,
                'status' => 'queued',
                'claimed_at' => null,
            ]);
        });

        return back()->with('status', 'Computador definido como impressora principal.');
    }

    public function revoke(Request $request, PrinterAgentDevice $device)
    {
        abort_unless($device->tenant_id === $request->user()->tenant_id, 404);

        DB::transaction(function () use ($request, $device): void {
            $wasPrimary = $device->is_primary;
            $device->update(['active' => false, 'is_primary' => false]);
            PrintJob::where('printer_agent_device_id', $device->id)->whereIn('status', ['queued', 'printing'])->update([
                'printer_agent_device_id' => null,
                'status' => 'queued',
                'claimed_at' => null,
            ]);

            if ($wasPrimary) {
                $replacement = PrinterAgentDevice::where('tenant_id', $request->user()->tenant_id)
                    ->where('active', true)->latest('last_seen_at')->first();
                if ($replacement) {
                    $replacement->update(['is_primary' => true]);
                    PrintJob::where('tenant_id', $replacement->tenant_id)->whereNull('printer_agent_device_id')->where('status', 'queued')
                        ->update(['printer_agent_device_id' => $replacement->id]);
                }
            }
        });

        return back()->with('status', 'Acesso deste computador revogado.');
    }

    public function reprint(Request $request, Order $order, ThermalPrintService $printer)
    {
        $tenant = $request->user()->tenant;
        abort_unless($order->venue()->where('tenant_id', $tenant->id)->exists(), 404);
        abort_unless(in_array($order->status, ['accepted', 'preparing', 'ready', 'out_for_delivery'], true), 422, 'Aceite o pedido antes de solicitar uma reimpressão.');
        abort_if($order->payment_mode === 'online' && $order->payment_status !== 'approved', 422, 'O pagamento precisa estar aprovado antes da impressão.');

        $job = $printer->queueKitchenTicketAgain($order);
        abort_unless($job, 422, 'Conecte uma impressora principal antes de solicitar a reimpressão.');
        AuditLog::create([
            'actor_id' => $request->user()->id,
            'tenant_id' => $tenant->id,
            'action' => 'printer.kitchen_ticket_reprinted',
            'subject_type' => Order::class,
            'subject_id' => $order->id,
            'metadata' => ['print_job_id' => $job->id],
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return back()->with('status', 'Reimpressão adicionada à fila.');
    }
}
