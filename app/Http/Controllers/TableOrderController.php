<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTableOrderRequest;
use App\Models\DiningTable;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\Tab;
use App\Services\InventoryService;
use App\Services\WhatsappNotifier;
use App\Support\Geofence;
use App\Support\VenueAvailability;
use App\Support\OrderLineBuilder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class TableOrderController extends Controller
{
    public function store(StoreTableOrderRequest $request, int $table)
    {
        $table = DiningTable::findOrFail($table);
        $venue = $request->attributes->get('venue');
        abort_unless($venue && $table->venue_id === $venue->id, 404);

        return $this->createOrder($request, $table);
    }

    public function storeByToken(StoreTableOrderRequest $request, string $token)
    {
        $table = DiningTable::where('qr_token', $token)
            ->whereHas('venue.tenant', fn ($query) => $query->where('status', 'active'))
            ->firstOrFail();

        return $this->createOrder($request, $table);
    }

    private function createOrder(StoreTableOrderRequest $request, DiningTable $table)
    {
        $venue = $table->venue()->firstOrFail();
        abort_unless(VenueAvailability::isOpen($venue), 422, 'O estabelecimento está fechado neste momento.');
        Geofence::assertInside($venue, $request->validated('latitude'), $request->validated('longitude'));

        return DB::transaction(function () use ($request, $table, $venue) {
            $table = DiningTable::lockForUpdate()->findOrFail($table->id);
            $consumer = Auth::guard('consumer')->user();
            $customerName = $consumer?->name ?: (trim((string) $request->validated('customer_name', '')) ?: null);
            $customerPhone = $consumer?->phone ?: $request->validated('customer_phone');
            $customer = $customerPhone ? Customer::updateOrCreate(['venue_id' => $venue->id, 'phone' => $customerPhone], ['name' => $customerName ?: 'Cliente', 'email' => $consumer?->email, 'last_order_at' => now()]) : null;
            $tabQuery = Tab::where('dining_table_id', $table->id)->where('status', 'open');
            $customerName ? $tabQuery->where('customer_name', $customerName) : $tabQuery->whereNull('customer_name');
            $tab = $tabQuery->lockForUpdate()->first();
            if (! $tab) {
                $tab = Tab::create(['venue_id' => $venue->id, 'dining_table_id' => $table->id, 'code' => 'M'.$table->code.'-'.now()->format('YmdHisv'), 'customer_name' => $customerName, 'opened_at' => now()]);
                $table->update(['status' => 'occupied']);
            }
            $lines = OrderLineBuilder::build($venue->id, $request->validated('items'));
            $order = Order::create(['venue_id' => $venue->id, 'tab_id' => $tab->id, 'customer_id' => $customer?->id, 'consumer_account_id' => $consumer?->id, 'public_token' => Str::random(48), 'channel' => 'table', 'status' => 'pending', 'customer_name' => $customerName, 'customer_phone' => $customerPhone, 'whatsapp_consent' => (bool) $request->validated('whatsapp_consent', false), 'subtotal' => 0, 'total' => 0, 'notes' => $request->validated('notes')]);
            $subtotal = 0;
            foreach ($lines as $line) {
                $p = $line['product'];
                $subtotal += $line['total'];
                $order->items()->create(['product_id' => $p->id, 'name_snapshot' => $p->name, 'unit_price' => $line['unit_price'], 'quantity' => $line['quantity'], 'total' => $line['total'], 'station' => $p->station, 'status' => 'pending', 'options_snapshot' => $line['options']]);
            }
            $order->update(['subtotal' => $subtotal, 'total' => $subtotal]);
            app(InventoryService::class)->reserve($order);
            DB::afterCommit(fn () => app(WhatsappNotifier::class)->queue($order->fresh(), 'order_received'));

            if ($customerName) {
                $request->session()->put('public_customer_name', $customerName);
            }

            return response()->json($order->load('items'), 201);
        });
    }
}
