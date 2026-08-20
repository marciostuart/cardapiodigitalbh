<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTableOrderRequest;
use App\Models\DiningTable;
use App\Models\Order;
use App\Models\Product;
use App\Models\Tab;
use Illuminate\Support\Facades\DB;

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
        $table = DiningTable::where('qr_token', $token)->firstOrFail();

        return $this->createOrder($request, $table);
    }

    private function createOrder(StoreTableOrderRequest $request, DiningTable $table)
    {
        $venue = $table->venue()->firstOrFail();

        return DB::transaction(function () use ($request, $table, $venue) {
            $table = DiningTable::lockForUpdate()->findOrFail($table->id);
            $customerName = trim((string) $request->validated('customer_name', '')) ?: null;
            $tabQuery = Tab::where('dining_table_id', $table->id)->where('status', 'open');
            $customerName ? $tabQuery->where('customer_name', $customerName) : $tabQuery->whereNull('customer_name');
            $tab = $tabQuery->lockForUpdate()->first();
            if (! $tab) {
                $tab = Tab::create(['venue_id' => $venue->id, 'dining_table_id' => $table->id, 'code' => 'M'.$table->code.'-'.now()->format('YmdHisv'), 'customer_name' => $customerName, 'opened_at' => now()]);
                $table->update(['status' => 'occupied']);
            }
            $ids = collect($request->validated('items'))->pluck('product_id')->unique();
            $products = Product::where('venue_id', $venue->id)->where('active', true)->whereIn('id', $ids)->get()->keyBy('id');
            abort_unless($products->count() === $ids->count(), 422, 'Produto indisponível para este estabelecimento.');
            $order = Order::create(['venue_id' => $venue->id, 'tab_id' => $tab->id, 'channel' => 'table', 'status' => 'pending', 'subtotal' => 0, 'total' => 0, 'notes' => $request->validated('notes')]);
            $subtotal = 0;
            foreach ($request->validated('items') as $line) {
                $p = $products[$line['product_id']];
                $total = $p->price * $line['quantity'];
                $subtotal += $total;
                $order->items()->create(['product_id' => $p->id, 'name_snapshot' => $p->name, 'unit_price' => $p->price, 'quantity' => $line['quantity'], 'total' => $total, 'station' => $p->station, 'status' => 'pending']);
            }
            $order->update(['subtotal' => $subtotal, 'total' => $subtotal]);

            return response()->json($order->load('items'), 201);
        });
    }
}
