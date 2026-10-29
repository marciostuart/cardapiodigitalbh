<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LegalTermsAcceptanceController extends Controller
{
    public function show(): View
    {
        return view('legal.acceptance', [
            'termsVersion' => config('legal.terms_version'),
            'privacyVersion' => config('legal.privacy_version'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate(['accept_terms' => ['required', 'accepted']]);

        $request->user()->termsAcceptances()->firstOrCreate(
            [
                'terms_version' => config('legal.terms_version'),
                'privacy_version' => config('legal.privacy_version'),
            ],
            ['accepted_at' => now()],
        );

        return redirect()->route('dashboard')->with('status', 'Aceite registrado.');
    }
}
