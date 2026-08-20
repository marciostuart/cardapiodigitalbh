<?php

namespace App\Http\Controllers;

use App\Models\DiningTable;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\SvgWriter;
use Illuminate\Http\Request;
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
        $result = (new SvgWriter)->write(new QrCode(route('public.qr-menu', $table->qr_token), size: 360, margin: 16));

        return response($result->getString(), 200, [
            'Content-Type' => $result->getMimeType(),
            'Content-Disposition' => 'inline; filename="mesa-'.$table->code.'.svg"',
            'Cache-Control' => 'private, max-age=3600',
        ]);
    }
}
