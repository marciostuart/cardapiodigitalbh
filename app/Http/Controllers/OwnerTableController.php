<?php

namespace App\Http\Controllers;

use App\Models\DiningTable;
use App\Models\AuditLog;
use App\Models\GeofenceOverride;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\SvgWriter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class OwnerTableController extends Controller
{
    public function index(Request $request)
    {
        $venue = $request->user()->tenant?->venues()->firstOrFail();

        return view('owner.tables', ['venue' => $venue, 'tables' => $venue->diningTables()->orderBy('code')->get()]);
    }

    public function store(Request $request)
    {
        $venue = $request->user()->tenant?->venues()->firstOrFail();
        $data = $request->validate(['code' => ['required', 'string', 'max:30'], 'name' => ['nullable', 'string', 'max:80']]);
        $firstTable = ! DiningTable::where('venue_id', $venue->id)->exists();
        DiningTable::create($data + ['venue_id' => $venue->id, 'qr_token' => (string) Str::uuid()]);

        return $firstTable
            ? redirect()->route('owner.catalog')->with('status', 'Mesa criada. Agora cadastre a primeira categoria e os produtos.')
            : back()->with('status', 'Mesa cadastrada.');
    }

    public function qr(Request $request, DiningTable $table)
    {
        $venue = $request->user()->tenant?->venues()->firstOrFail();
        abort_unless($table->venue_id === $venue->id, 404);
        $url = route('public.tenant-table', ['subdomain' => $venue->subdomain, 'token' => $table->qr_token]);
        $result = (new SvgWriter)->write(new QrCode($url, size: 360, margin: 16));

        return response($result->getString(), 200, [
            'Content-Type' => $result->getMimeType(),
            'Content-Disposition' => 'inline; filename="mesa-'.$table->code.'.svg"',
            'Cache-Control' => 'private, max-age=3600',
        ]);
    }

    public function createGeofenceOverride(Request $request, DiningTable $table)
    {
        $venue = $request->user()->tenant?->venues()->firstOrFail();
        abort_unless($table->venue_id === $venue->id, 404);
        abort_unless($venue->geofence_enabled, 422, 'Ative o geofence nas configurações antes de gerar códigos temporários.');

        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $expiresAt = now()->addMinutes(15);

        DB::transaction(function () use ($request, $venue, $table, $code, $expiresAt): void {
            GeofenceOverride::where('dining_table_id', $table->id)->whereNull('used_at')->delete();
            $override = GeofenceOverride::create([
                'tenant_id' => $venue->tenant_id,
                'venue_id' => $venue->id,
                'dining_table_id' => $table->id,
                'created_by' => $request->user()->id,
                'code_hash' => hash_hmac('sha256', $table->id.'|'.$code, (string) config('app.key')),
                'expires_at' => $expiresAt,
            ]);
            AuditLog::create([
                'actor_id' => $request->user()->id,
                'tenant_id' => $venue->tenant_id,
                'action' => 'geofence.override_created',
                'subject_type' => GeofenceOverride::class,
                'subject_id' => $override->id,
                'metadata' => ['dining_table_id' => $table->id, 'expires_at' => $expiresAt->toIso8601String()],
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
        });

        return back()->with('geofence_access_code', $code)->with('geofence_access_code_table', $table->code)->with('geofence_access_code_expires_at', $expiresAt->format('d/m/Y H:i'));
    }
}
