<?php

namespace App\Http\Controllers;

use App\Models\VenueSchedule;
use App\Support\VenueAvailability;
use Illuminate\Http\Request;

class OwnerScheduleController extends Controller
{
    public function index(Request $request)
    {
        $venue = $request->user()->tenant->venues()->firstOrFail();
        return view('owner.schedules', ['venue' => $venue, 'schedules' => $venue->schedules()->orderBy('day_of_week')->orderBy('opens_at')->get(), 'isOpen' => VenueAvailability::isOpen($venue)]);
    }

    public function store(Request $request)
    {
        $venue = $request->user()->tenant->venues()->firstOrFail();
        $data = $request->validate(['day_of_week' => ['required', 'integer', 'between:0,6'], 'opens_at' => ['required', 'date_format:H:i'], 'closes_at' => ['required', 'date_format:H:i']]);
        abort_if($data['opens_at'] === $data['closes_at'], 422, 'Abertura e fechamento devem ser diferentes.');
        $venue->schedules()->create($data);
        return back()->with('status', 'Horário adicionado.');
    }

    public function destroy(Request $request, VenueSchedule $schedule)
    {
        $venue = $request->user()->tenant->venues()->firstOrFail();
        abort_unless($schedule->venue_id === $venue->id, 404);
        $schedule->delete();
        return back()->with('status', 'Horário removido.');
    }

    public function override(Request $request)
    {
        $venue = $request->user()->tenant->venues()->firstOrFail();
        $data = $request->validate(['state' => ['nullable', 'in:open,closed'], 'minutes' => ['nullable', 'integer', 'between:5,1440']]);
        $venue->update(['manual_open_state' => $data['state'] ?: null, 'manual_open_until' => $data['state'] ? now()->addMinutes((int) ($data['minutes'] ?? 60)) : null]);
        return back()->with('status', $data['state'] ? 'Estado manual aplicado temporariamente.' : 'Funcionamento automático restaurado.');
    }
}
