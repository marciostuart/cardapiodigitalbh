<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\PlatformSetting;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class PlatformController extends Controller
{
    public function index(Request $request)
    {
        $tenants = Tenant::with(['venues', 'evolutionInstance'])
            ->when($request->string('search')->trim()->isNotEmpty(), function ($query) use ($request) {
                $search = '%'.$request->string('search')->trim().'%';
                $query->where(fn ($inner) => $inner->where('name', 'like', $search)->orWhere('slug', 'like', $search));
            })->latest()->paginate(30)->withQueryString();
        $gtmId = PlatformSetting::value('gtm_container_id');

        return view('admin.platform', compact('tenants', 'gtmId'));
    }

    public function updateMeasurement(Request $request)
    {
        $data = $request->validate([
            'gtm_container_id' => ['nullable', 'regex:/^GTM-[A-Z0-9]{4,20}$/'],
        ]);

        PlatformSetting::putValue('gtm_container_id', $data['gtm_container_id'] ?? null);

        return back()->with('status', 'Configuração global de mensuração atualizada.');
    }

    public function updatePlan(Request $request, Tenant $tenant)
    {
        $data = $request->validate(['plan' => ['required', Rule::exists('plans', 'slug')->where('active', true)], 'status' => ['required', 'in:active,suspended']]);
        $before = $tenant->only(['plan', 'status']);
        $tenant->update($data);
        $this->audit($request, $tenant, 'tenant.plan_updated', ['before' => $before, 'after' => $data]);
        return back()->with('status', 'Plano atualizado.');
    }

    public function impersonate(Request $request, Tenant $tenant)
    {
        $user = User::where('tenant_id', $tenant->id)->where('role', 'owner')->first();
        if (! $user) {
            $user = User::create([
                'tenant_id' => $tenant->id,
                'name' => $tenant->name,
                'email' => 'proprietario+'.Str::slug($tenant->slug).'@cardapiodigitalbh.com.br',
                'password' => Str::random(48),
                'role' => 'owner',
                'active' => true,
            ]);
        }
        $platformOwnerId = Auth::id();
        $this->audit($request, $tenant, 'tenant.impersonation_started', ['customer_owner_id' => $user->id]);
        $request->session()->put('impersonator_id', $platformOwnerId);
        $request->session()->put('impersonated_tenant_id', $tenant->id);
        Auth::login($user);
        $request->session()->regenerate();
        return redirect()->route('dashboard')->with('status', 'Você está visualizando a conta do cliente.');
    }

    public function leave(Request $request)
    {
        $id = $request->session()->pull('impersonator_id');
        $tenantId = $request->session()->pull('impersonated_tenant_id');
        abort_unless($id, 403);
        $owner = User::whereKey($id)->where('role', 'platform_owner')->firstOrFail();
        AuditLog::create(['actor_id' => $owner->id, 'tenant_id' => $tenantId, 'action' => 'tenant.impersonation_finished', 'metadata' => [], 'ip_address' => $request->ip(), 'user_agent' => $request->userAgent()]);
        Auth::login($owner);
        $request->session()->regenerate();
        return redirect()->route('platform.dashboard');
    }

    private function audit(Request $request, Tenant $tenant, string $action, array $metadata): void
    {
        AuditLog::create(['actor_id' => $request->user()->id, 'tenant_id' => $tenant->id, 'action' => $action, 'subject_type' => Tenant::class, 'subject_id' => $tenant->id, 'metadata' => $metadata, 'ip_address' => $request->ip(), 'user_agent' => $request->userAgent()]);
    }
}
