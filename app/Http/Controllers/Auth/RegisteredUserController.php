<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Venue;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->merge(['subdomain' => Str::slug((string) $request->input('subdomain'))]);
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'venue_name' => ['required', 'string', 'max:120'],
            'subdomain' => ['required', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', 'min:3', 'max:50', 'not_in:www,admin,app,api,mail,painel,suporte', 'unique:venues,subdomain'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::min(8)->mixedCase()->numbers()],
        ]);

        $user = DB::transaction(function () use ($request) {
            $tenant = Tenant::create([
                'name' => $request->venue_name,
                'slug' => $request->subdomain,
                'plan' => 'free',
                'status' => 'active',
            ]);

            Venue::create([
                'tenant_id' => $tenant->id,
                'name' => $request->venue_name,
                'subdomain' => $request->subdomain,
            ]);

            return User::create([
                'tenant_id' => $tenant->id,
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'role' => 'owner',
            ]);
        });

        event(new Registered($user));

        Auth::login($user);

        return redirect(route('dashboard', absolute: false));
    }
}
