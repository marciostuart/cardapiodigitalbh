<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\EvolutionInstance;
use App\Models\Tenant;
use App\Services\EvolutionApi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Throwable;

class WhatsappSupportController extends Controller
{
    public function reset(Request $request, Tenant $tenant, EvolutionApi $api)
    {
        $instance = $tenant->evolutionInstance;
        abort_unless($instance, 404, 'Este estabelecimento não possui instância de WhatsApp.');
        try {
            $api->delete($instance->instance_name);
        } catch (Throwable $error) {
            report($error);
            return back()->withErrors(['whatsapp' => 'A Evolution API recusou o reset. Nenhuma outra instância foi alterada.']);
        }
        DB::transaction(function () use ($request, $tenant, $instance) {
            AuditLog::create(['actor_id' => $request->user()->id, 'tenant_id' => $tenant->id, 'action' => 'whatsapp.instance_reset', 'subject_type' => EvolutionInstance::class, 'subject_id' => $instance->id, 'metadata' => ['instance' => $instance->instance_name], 'ip_address' => $request->ip(), 'user_agent' => $request->userAgent()]);
            $instance->delete();
        });
        return back()->with('status', 'WhatsApp redefinido somente para '.$tenant->name.'. O estabelecimento já pode conectar novamente.');
    }
}
