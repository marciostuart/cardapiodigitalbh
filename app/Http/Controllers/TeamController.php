<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;

class TeamController extends Controller
{
    public function index(Request $request)
    {
        $members = User::where('tenant_id', $request->user()->tenant_id)->where('id', '!=', $request->user()->id)->orderBy('name')->get();

        return view('owner.team', compact('members'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'role' => ['required', 'in:waiter,kitchen,cashier'],
            'password' => ['required', 'confirmed', Password::min(8)->mixedCase()->numbers()],
        ]);
        $member = User::create($data + ['tenant_id' => $request->user()->tenant_id, 'active' => true]);
        event(new Registered($member));

        return back()->with('status', 'Membro cadastrado. Enviamos um link de verificação; o acesso ao painel será liberado após a confirmação do e-mail.');
    }

    public function update(Request $request, User $member)
    {
        $this->authorizeMember($request, $member);
        $data = $request->validate(['role' => ['required', 'in:waiter,kitchen,cashier'], 'active' => ['required', 'boolean']]);
        $member->update($data);

        return back()->with('status', 'Acesso da equipe atualizado.');
    }

    public function resetPassword(Request $request, User $member)
    {
        $this->authorizeMember($request, $member);
        $data = $request->validate(['password' => ['required', 'confirmed', Password::min(8)->mixedCase()->numbers()]]);
        $member->update($data);

        return back()->with('status', 'Senha temporária atualizada.');
    }

    private function authorizeMember(Request $request, User $member): void
    {
        abort_unless($member->tenant_id === $request->user()->tenant_id && $member->role !== 'owner', 404);
    }
}
