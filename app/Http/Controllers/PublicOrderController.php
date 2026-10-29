<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePublicOrderRequest;
use App\Models\Coupon;
use App\Models\Customer;
use App\Models\DeliveryZone;
use App\Models\Order;
use App\Models\OnlinePayment;
use App\Models\MercadoPagoConnection;
use App\Jobs\ExpireOnlinePayment;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Services\InventoryService;
use App\Services\WhatsappNotifier;
use App\Services\WebPushNotifier;
use App\Services\MercadoPagoService;
use App\Support\VenueAvailability;
use App\Support\OrderLineBuilder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Throwable;

class PublicOrderController extends Controller
{
    public function store(StorePublicOrderRequest $request)
    {
        $venue = $request->attributes->get('venue');
        abort_unless($venue && VenueAvailability::isOpen($venue), 422, 'O estabelecimento está fechado neste momento.');
        $channel = $request->validated('channel');
        abort_if($channel === 'pickup' && ! $venue->pickup_enabled, 422, 'Retirada indisponível.');
        abort_if($channel === 'delivery' && ! $venue->delivery_enabled, 422, 'Delivery indisponível.');
        $paymentMode = $request->validated('payment_mode', 'offline');
        if ($paymentMode === 'online') {
            abort_unless(config('services.mercadopago.client_id') && config('services.mercadopago.client_secret') && config('services.mercadopago.webhook_secret'), 503, 'O pagamento online está temporariamente indisponível.');
            abort_unless(MercadoPagoConnection::where('tenant_id', $venue->tenant_id)->exists(), 422, 'Este estabelecimento ainda não habilitou pagamento online. Escolha pagar ao retirar ou receber o pedido.');
        }

        $order = DB::transaction(function () use ($request, $venue, $channel, $paymentMode) {
            $consumer = Auth::guard('consumer')->user()?->loadMissing('defaultAddress');
            $rawPhone = $consumer?->phone ?: $request->validated('customer_phone');
            $phone = $rawPhone ? $this->normalizePhone($rawPhone) : null;
            abort_if($rawPhone && ! $phone, 422, 'Informe um WhatsApp válido com DDD.');
            abort_if($request->boolean('whatsapp_consent') && ! $phone, 422, 'Informe um WhatsApp válido para receber as atualizações.');
            $customerName = $consumer?->name ?: (trim((string) $request->validated('customer_name', '')) ?: null);
            $customer = $phone ? Customer::updateOrCreate(['venue_id' => $venue->id, 'phone' => $phone], ['name' => $customerName ?: 'Cliente', 'email' => $consumer?->email, 'marketing_consent' => (bool) $request->validated('marketing_consent', false), 'last_order_at' => now()]) : null;
            $address = null;
            $zone = null;
            if ($channel === 'delivery') {
                $zone = DeliveryZone::whereKey($request->validated('delivery_zone_id'))->where('venue_id', $venue->id)->where('active', true)->first();
                abort_unless($zone, 422, 'A região de entrega selecionada não está disponível. Escolha outra região para continuar.');
                $address = collect($request->validated())->only(['postal_code', 'street', 'number', 'complement', 'neighborhood', 'city', 'state', 'reference'])->filter(fn ($value) => $value !== null && $value !== '')->all();
                if (count($address) < 6 && $consumer?->defaultAddress) $address = $consumer->defaultAddress->only(['postal_code', 'street', 'number', 'complement', 'neighborhood', 'city', 'state', 'reference']);
                if ($customer) $customer->addresses()->create($address);
            }

            $lines = OrderLineBuilder::build($venue->id, $request->validated('items'));
            $subtotal = collect($lines)->sum('total');
            abort_if($subtotal + 0.009 < (float) $venue->minimum_order, 422, 'O pedido mínimo é R$ '.number_format((float) $venue->minimum_order, 2, ',', '.').'.');
            [$coupon, $discount] = $this->coupon($venue->id, $request->validated('coupon_code'), $subtotal);
            $fee = $zone ? (float) $zone->fee : 0;
            $total = max(0, $subtotal - $discount + $fee);
            $changeFor = $request->validated('change_for');
            abort_if($changeFor !== null && (float) $changeFor + 0.009 < $total, 422, 'O valor para troco deve ser maior que o total.');

            abort_if($paymentMode === 'online' && $total <= 0, 422, 'O total precisa ser maior que zero para pagamento online.');
            $order = Order::create(['venue_id' => $venue->id, 'customer_id' => $customer?->id, 'consumer_account_id' => $consumer?->id, 'delivery_zone_id' => $zone?->id, 'public_token' => Str::random(48), 'channel' => $channel, 'status' => 'pending', 'customer_name' => $customerName, 'customer_phone' => $phone, 'whatsapp_consent' => (bool) $request->validated('whatsapp_consent', false), 'delivery_address' => $address, 'coupon_code' => $coupon?->code, 'discount' => $discount, 'delivery_fee' => $fee, 'payment_method' => $request->validated('payment_method'), 'payment_mode' => $paymentMode, 'payment_status' => $paymentMode === 'online' ? 'pending' : 'not_required', 'change_for' => $changeFor, 'subtotal' => $subtotal, 'total' => $total, 'notes' => $request->validated('notes')]);
            foreach ($lines as $line) {
                $product = $line['product'];
                $order->items()->create(['product_id' => $product->id, 'name_snapshot' => $product->name, 'unit_price' => $line['unit_price'], 'quantity' => $line['quantity'], 'total' => $line['total'], 'station' => $product->station, 'status' => 'pending', 'options_snapshot' => $line['options']]);
            }
            if ($paymentMode === 'online') {
                $onlinePayment = OnlinePayment::create([
                    'tenant_id' => $venue->tenant_id,
                    'order_id' => $order->id,
                    'external_reference' => (string) Str::uuid(),
                    'amount' => $total,
                    'currency' => 'BRL',
                    'status' => 'creating',
                    'expires_at' => now()->addMinutes(30),
                ]);
                DB::afterCommit(fn () => ExpireOnlinePayment::dispatch($onlinePayment->id)->delay($onlinePayment->expires_at)->onQueue('payments'));
            }
            app(InventoryService::class)->reserve($order);
            if ($coupon) $coupon->increment('used_count');
            if ($paymentMode === 'offline') {
                DB::afterCommit(fn () => app(WhatsappNotifier::class)->queue($order->fresh(), 'order_received'));
                DB::afterCommit(fn () => app(WebPushNotifier::class)->queueNewOrder($order->fresh()));
            }
            return $order;
        });

        if ($order->customer_name) {
            $request->session()->put('public_customer_name', $order->customer_name);
        }

        $trackingUrl = route('public.order.track', ['subdomain' => $venue->subdomain, 'token' => $order->public_token]);
        $checkoutUrl = null;
        if ($paymentMode === 'online') {
            $payment = OnlinePayment::where('order_id', $order->id)->firstOrFail();
            try {
                $checkoutUrl = app(MercadoPagoService::class)->createPreference($payment)['checkout_url'];
            } catch (Throwable $error) {
                report($error);
                $payment->update(['status' => 'failed']);
                $order->update(['payment_status' => 'failed']);
            }
        }

        return response()->json([
            'order' => $order->fresh()->load('items'),
            'tracking_url' => $trackingUrl,
            'checkout_url' => $checkoutUrl,
            'payment_mode' => $paymentMode,
        ], 201);
    }

    public function pay(string $subdomain, string $token)
    {
        $order = Order::where('public_token', $token)->with('venue')->firstOrFail();
        abort_unless($order->venue->subdomain === $subdomain && $order->payment_mode === 'online', 404);
        abort_if($order->status === 'cancelled' || $order->payment_status === 'expired', 409, 'Este pedido não pode mais ser pago.');
        if ($order->payment_status === 'approved') {
            return redirect()->route('public.order.track', ['subdomain' => $subdomain, 'token' => $token]);
        }

        $payment = DB::transaction(function () use ($order) {
            $currentOrder = Order::lockForUpdate()->findOrFail($order->id);
            abort_if($currentOrder->status === 'cancelled' || $currentOrder->payment_status === 'expired', 409, 'Este pedido não pode mais ser pago.');
            $current = OnlinePayment::where('order_id', $currentOrder->id)->lockForUpdate()->firstOrFail();
            if ($current->status === 'approved') {
                return null;
            }
            if ($current->checkout_url && $current->expires_at?->isFuture() && $current->status === 'pending') {
                return $current;
            }
            $current->update(['status' => 'creating', 'expires_at' => now()->addMinutes(30)]);

            return $current->fresh();
        });
        if (! $payment) {
            return redirect()->route('public.order.track', ['subdomain' => $subdomain, 'token' => $token]);
        }
        if ($payment->checkout_url && $payment->expires_at?->isFuture() && $payment->status === 'pending') {
            return redirect()->away($payment->checkout_url);
        }

        try {
            $checkout = app(MercadoPagoService::class)->createPreference($payment);
            $order->update(['payment_status' => 'pending']);

            return redirect()->away($checkout['checkout_url']);
        } catch (Throwable $error) {
            report($error);
            $payment->update(['status' => 'failed', 'expires_at' => now()->addMinutes(30)]);
            $order->update(['payment_status' => 'failed']);
            ExpireOnlinePayment::dispatch($payment->id)->delay($payment->expires_at)->onQueue('payments');

            return redirect()->route('public.order.track', ['subdomain' => $subdomain, 'token' => $token])->withErrors(['payment' => 'Não conseguimos iniciar o pagamento agora. Tente novamente em alguns instantes.']);
        }
    }

    public function track(string $subdomain, string $token)
    {
        $order = Order::where('public_token', $token)->with(['items', 'venue', 'onlinePayment'])->firstOrFail();
        abort_unless($order->venue->subdomain === $subdomain, 404);
        $venue = $order->venue;
        $categories = ProductCategory::where('venue_id', $venue->id)->where('active', true)->orderBy('position')->get();
        return view('public.order-track', compact('order', 'venue', 'categories') + ['isOpen' => VenueAvailability::isOpen($venue)]);
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
