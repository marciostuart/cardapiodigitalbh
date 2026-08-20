<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Plan;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PlanController extends Controller
{
    private const FEATURES = ['catalog', 'tables', 'inventory', 'ingredients', 'delivery', 'pickup', 'geofence', 'whatsapp', 'printing', 'reports', 'payments', 'banners', 'marketplace'];

    public function index()
    {
        return view('admin.plans', ['plans' => Plan::orderBy('position')->get(), 'featureKeys' => self::FEATURES]);
    }

    public function store(Request $request)
    {
        $plan = Plan::create($this->validated($request));
        $this->audit($request, 'plan.created', $plan);
        return back()->with('status', 'Plano criado.');
    }

    public function update(Request $request, Plan $plan)
    {
        $plan->update($this->validated($request, $plan));
        $this->audit($request, 'plan.updated', $plan);
        return back()->with('status', 'Plano atualizado.');
    }

    private function validated(Request $request, ?Plan $plan = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'slug' => ['required', 'alpha_dash', 'max:50', Rule::unique('plans', 'slug')->ignore($plan)],
            'description' => ['nullable', 'string', 'max:500'],
            'monthly_price' => ['required', 'numeric', 'min:0'],
            'product_limit' => ['nullable', 'integer', 'min:1'],
            'position' => ['required', 'integer', 'min:0'],
            'active' => ['required', 'boolean'],
            'features' => ['nullable', 'array'],
            'features.*' => [Rule::in(self::FEATURES)],
        ]);
        $selected = array_fill_keys($data['features'] ?? [], true);
        $data['features'] = collect(self::FEATURES)->mapWithKeys(fn ($feature) => [$feature => isset($selected[$feature])])->all();
        $data['limits'] = isset($data['product_limit']) ? ['products' => (int) $data['product_limit']] : [];
        unset($data['product_limit']);
        return $data;
    }

    private function audit(Request $request, string $action, Plan $plan): void
    {
        AuditLog::create(['actor_id' => $request->user()->id, 'action' => $action, 'subject_type' => Plan::class, 'subject_id' => $plan->id, 'metadata' => ['slug' => $plan->slug], 'ip_address' => $request->ip(), 'user_agent' => $request->userAgent()]);
    }
}
