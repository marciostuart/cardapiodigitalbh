<?php

namespace App\Http\Controllers;

use App\Models\ProductCategory;
use App\Models\MercadoPagoConnection;
use App\Support\CatalogOrdering;
use App\Support\VenueAvailability;
use Illuminate\Http\Request;

class PublicMenuController extends Controller
{
    public function show(Request $request)
    {
        $venue = $request->attributes->get('venue');
        abort_unless($venue, 404);

        return view('public.menu', [
            'venue' => $venue,
            'onlinePaymentsEnabled' => filled(config('services.mercadopago.client_id'))
                && filled(config('services.mercadopago.client_secret'))
                && filled(config('services.mercadopago.webhook_secret'))
                && MercadoPagoConnection::where('tenant_id', $venue->tenant_id)->exists(),
            'isOpen' => VenueAvailability::isOpen($venue),
            'deliveryZones' => $venue->deliveryZones()->where('active', true)->orderBy('name')->get(),
            'banners' => $venue->banners()->where('active', true)->where(fn ($q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', now()))->where(fn ($q) => $q->whereNull('ends_at')->orWhere('ends_at', '>=', now()))->orderBy('position')->get(),
            'categories' => ProductCategory::where('venue_id', $venue->id)
                ->where('active', true)
                ->with(['products' => fn ($query) => CatalogOrdering::apply($query->where('active', true), $venue->catalog_sort_mode)->with(['images', 'optionGroups' => fn ($q) => $q->where('active', true)->with(['options' => fn ($q) => $q->where('active', true)])])])
                ->orderBy('position')->get(),
        ]);
    }
}
