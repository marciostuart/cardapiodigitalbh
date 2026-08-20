<?php

namespace App\Http\Controllers;

use App\Models\Coupon;
use App\Models\DeliveryZone;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class OwnerCommerceController extends Controller
{
    public function index(Request $request)
    {
        $venue = $request->user()->tenant->venues()->firstOrFail();
        return view('owner.commerce', ['venue' => $venue, 'coupons' => $venue->coupons()->latest()->get(), 'zones' => $venue->deliveryZones()->orderBy('name')->get(), 'customers' => $venue->customers()->withCount('orders')->latest('last_order_at')->limit(100)->get()]);
    }

    public function storeCoupon(Request $request)
    {
        $venue = $request->user()->tenant->venues()->firstOrFail();
        $data = $request->validate(['code' => ['required', 'alpha_dash', 'max:40'], 'type' => ['required', 'in:fixed,percent'], 'value' => ['required', 'numeric', 'gt:0'], 'minimum_order' => ['nullable', 'numeric', 'min:0'], 'maximum_discount' => ['nullable', 'numeric', 'gt:0'], 'usage_limit' => ['nullable', 'integer', 'min:1'], 'starts_at' => ['nullable', 'date'], 'expires_at' => ['nullable', 'date', 'after:starts_at']]);
        $data['code'] = Str::upper($data['code']);
        $venue->coupons()->create($data);
        return back()->with('status', 'Cupom cadastrado.');
    }

    public function updateCoupon(Request $request, Coupon $coupon)
    {
        $venue = $request->user()->tenant->venues()->firstOrFail();
        abort_unless($coupon->venue_id === $venue->id, 404);
        $coupon->update($request->validate(['active' => ['required', 'boolean']]));
        return back()->with('status', 'Cupom atualizado.');
    }

    public function storeZone(Request $request)
    {
        $venue = $request->user()->tenant->venues()->firstOrFail();
        $venue->deliveryZones()->create($request->validate(['name' => ['required', 'string', 'max:100'], 'fee' => ['required', 'numeric', 'min:0'], 'neighborhoods' => ['nullable', 'string', 'max:1000']]));
        return back()->with('status', 'Região de entrega cadastrada.');
    }

    public function updateZone(Request $request, DeliveryZone $zone)
    {
        $venue = $request->user()->tenant->venues()->firstOrFail();
        abort_unless($zone->venue_id === $venue->id, 404);
        $zone->update($request->validate(['name' => ['required', 'string', 'max:100'], 'fee' => ['required', 'numeric', 'min:0'], 'neighborhoods' => ['nullable', 'string', 'max:1000'], 'active' => ['required', 'boolean']]));
        return back()->with('status', 'Região de entrega atualizada.');
    }
}
