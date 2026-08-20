<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePublicOrderRequest;
use App\Models\Coupon;
use App\Models\Customer;
use App\Models\DeliveryZone;
use App\Models\Order;
use App\Models\Product;
use App\Services\InventoryService;
use App\Services\WhatsappNotifier;
use App\Support\VenueAvailability;
use App\Support\OrderLineBuilder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PublicOrderController extends Controller
{
    public function store(StorePublicOrderRequest $request)
    {
        $venue = $request->attributes->get('venue');
        abort_unless($venue && VenueAvailability::isOpen($venue), 422, 'O estabelecimento está fechado neste momento.');
        $channel = $request->validated('channel');
        abort_if($channel === 'pickup' && ! $venue->pickup_enabled, 422, 'Retirada indisponível.');
        abort_if($channel === 'delivery' && ! $venue->delivery_enabled, 422, 'Delivery indisponível.');

        $order = DB::transaction(function () use ($request, $venue, $channel) {
            $phone = $this->normalizePhone($request->validated('customer_phone'));
            abort_unless($phone, 422, 'Informe um WhatsApp válido com DDD.');
            $customer = Customer::updateOrCreate(['venue_id' => $venue->id, 'phone' => $phone], ['name' => $request->validated('customer_name'), 'marketing_consent' => (bool) $request->validated('marketing_consent', false), 'last_order_at' => now()]);
            $address = null;
            $zone = null;
            if ($channel === 'delivery') {
                $zone = DeliveryZone::whereKey($request->validated('delivery_zone_id'))->where('venue_id', $venue->id)->where('active', true)->firstOrFail();
                $address = collect($request->validated())->only(['postal_code', 'street', 'number', 'complement', 'neighborhood', 'city', 'state', 'reference'])->all();
                $customer->addresses()->create($address);
            }

            $lines = OrderLineBuilder::build($venue->id, $request->validated('items'));
            $subtotal = collect($lines)->sum('total');
            abort_if($subtotal + 0.009 < (float) $venue->minimum_order, 422, 'O pedido mínimo é R$ '.number_format((float) $venue->minimum_order, 2, ',', '.').'.');
            [$coupon, $discount] = $this->coupon($venue->id, $request->validated('coupon_code'), $subtotal);
            $fee = $zone ? (float) $zone->fee : 0;
            $total = max(0, $subtotal - $discount + $fee);
            $changeFor = $request->validated('change_for');
            abort_if($changeFor !== null && (float) $changeFor + 0.009 < $total, 422, 'O valor para troco deve ser maior que o total.');

            $order = Order::create(['venue_id' => $venue->id, 'customer_id' => $customer->id, 'delivery_zone_id' => $zone?->id, 'public_token' => Str::random(48), 'channel' => $channel, 'status' => 'pending', 'customer_name' => $customer->name, 'customer_phone' => $phone, 'whatsapp_consent' => (bool) $request->validated('whatsapp_consent', false), 'delivery_address' => $address, 'coupon_code' => $coupon?->code, 'discount' => $discount, 'delivery_fee' => $fee, 'payment_method' => $request->validated('payment_method'), 'change_for' => $changeFor, 'subtotal' => $subtotal, 'total' => $total, 'notes' => $request->validated('notes')]);
            foreach ($lines as $line) {
                $product = $line['product'];
                $order->items()->create(['product_id' => $product->id, 'name_snapshot' => $product->name, 'unit_price' => $line['unit_price'], 'quantity' => $line['quantity'], 'total' => $line['total'], 'station' => $product->station, 'status' => 'pending', 'options_snapshot' => $line['options']]);
            }
            app(InventoryService::class)->reserve($order);
            if ($coupon) $coupon->increment('used_count');
            DB::afterCommit(fn () => app(WhatsappNotifier::class)->queue($order->fresh(), 'order_received'));
            return $order;
        });

        return response()->json(['order' => $order->load('items'), 'tracking_url' => route('public.order.track', ['subdomain' => $venue->subdomain, 'token' => $order->public_token])], 201);
    }

    public function track(string $subdomain, string $token)
    {
        $order = Order::where('public_token', $token)->with(['items', 'venue'])->firstOrFail();
        abort_unless($order->venue->subdomain === $subdomain, 404);
        return view('public.order-track', compact('order'));
    }

    private function coupon(int $venueId, ?string $code, float $subtotal): array
    {
        if (! $code) return [null, 0];
        $coupon = Coupon::where('venue_id', $venueId)->whereRaw('upper(code) = ?', [Str::upper(trim($code))])->where('active', true)->lockForUpdate()->first();
        abort_unless($coupon, 422, 'Cupom inválido.');
        abort_if($coupon->starts_at?->isFuture() || $coupon->expires_at?->isPast() || ($coupon->usage_limit !== null && $coupon->used_count >= $coupon->usage_limit), 422, 'Cupom expirado ou esgotado.');
        abort_if($subtotal + 0.009 < (float) $coupon->minimum_order, 422, 'Este cupom exige um valor mínimo maior.');
        $discount = $coupon->type === 'percent' ? $subtotal * ((float) $coupon->value / 100) : (float) $coupon->value;
        if ($coupon->maximum_discount !== null) $discount = min($discount, (float) $coupon->maximum_discount);
        return [$coupon, min($discount, $subtotal)];
    }

    private function normalizePhone(string $phone): ?string
    {
        $digits = preg_replace('/\D+/', '', $phone);
        if (in_array(strlen($digits), [10, 11], true)) $digits = '55'.$digits;
        return in_array(strlen($digits), [12, 13], true) && str_starts_with($digits, '55') ? $digits : null;
    }
}
