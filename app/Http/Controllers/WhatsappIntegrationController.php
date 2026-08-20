<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\EvolutionInstance;
use App\Models\OutboundMessage;
use App\Models\WhatsappTemplate;
use App\Services\EvolutionApi;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Throwable;

class WhatsappIntegrationController extends Controller
{
    public function index(Request $request)
    {
        $tenant = $request->user()->tenant;
        $global = WhatsappTemplate::whereNull('tenant_id')->orderBy('id')->get();
        $overrides = WhatsappTemplate::where('tenant_id', $tenant->id)->get()->keyBy('event');
        return view('owner.whatsapp', ['instance' => $tenant->evolutionInstance, 'qrCode' => session('whatsapp_qr'), 'templates' => $global->map(fn ($template) => $overrides->get($template->event) ?: $template), 'messages' => OutboundMessage::where('tenant_id', $tenant->id)->latest()->limit(20)->get()]);
    }

    public function connect(Request $request, EvolutionApi $api)
    {
        $tenant = $request->user()->tenant;
        $instance = $tenant->evolutionInstance ?: EvolutionInstance::create(['tenant_id' => $tenant->id, 'instance_name' => 'cdbh-'.$tenant->id.'-'.Str::lower(Str::random(8)), 'status' => 'connecting']);
        try {
            $payload = $instance->wasRecentlyCreated ? $api->create($instance->instance_name) : $api->connect($instance->instance_name);
            $instance->update(['status' => 'connecting', 'last_error' => null, 'last_checked_at' => now()]);
            $this->audit($request, 'whatsapp.connect_started', $instance);
            return back()->with('status', 'Leia o QR Code com o WhatsApp do estabelecimento.')->with('whatsapp_qr', EvolutionApi::qrCode($payload));
        } catch (Throwable $error) {
            $instance->update(['status' => 'degraded', 'last_error' => Str::limit($error->getMessage(), 1000), 'last_checked_at' => now()]);
            report($error);
            return back()->withErrors(['whatsapp' => 'Não foi possível iniciar a conexão. Tente novamente ou solicite o reset ao suporte.']);
        }
    }

    public function refresh(Request $request, EvolutionApi $api)
    {
        $instance = $request->user()->tenant->evolutionInstance;
        abort_unless($instance, 404);
        try {
            $state = EvolutionApi::state($api->connectionState($instance->instance_name));
            $instance->update(['status' => $state, 'connected_at' => $state === 'connected' ? ($instance->connected_at ?: now()) : null, 'last_error' => null, 'last_checked_at' => now()]);
            return back()->with('status', $state === 'connected' ? 'WhatsApp conectado.' : 'WhatsApp ainda não está conectado.');
        } catch (Throwable $error) {
            $instance->update(['status' => 'degraded', 'last_error' => Str::limit($error->getMessage(), 1000), 'last_checked_at' => now()]);
            report($error);
            return back()->withErrors(['whatsapp' => 'Não foi possível consultar a conexão agora.']);
        }
    }

    public function updateTemplate(Request $request, string $event)
    {
        $tenant = $request->user()->tenant;
        $global = WhatsappTemplate::whereNull('tenant_id')->where('event', $event)->firstOrFail();
        $data = $request->validate(['body' => ['required', 'string', 'max:1000'], 'active' => ['required', 'boolean']]);
        preg_match_all('/\{\{([a-z_]+)\}\}/', $data['body'], $matches);
        $unknown = array_diff($matches[1] ?? [], ['customer_name', 'order_number', 'venue_name', 'order_total']);
        abort_if($unknown !== [], 422, 'O modelo contém variáveis não permitidas.');
        WhatsappTemplate::updateOrCreate(['tenant_id' => $tenant->id, 'event' => $event], ['name' => $global->name, 'body' => $data['body'], 'active' => $data['active']]);
        return back()->with('status', 'Mensagem personalizada.');
    }

    private function audit(Request $request, string $action, EvolutionInstance $instance): void
    {
        AuditLog::create(['actor_id' => $request->user()->id, 'tenant_id' => $instance->tenant_id, 'action' => $action, 'subject_type' => EvolutionInstance::class, 'subject_id' => $instance->id, 'metadata' => ['instance' => $instance->instance_name], 'ip_address' => $request->ip(), 'user_agent' => $request->userAgent()]);
    }
}
