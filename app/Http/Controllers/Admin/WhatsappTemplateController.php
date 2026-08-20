<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\WhatsappTemplate;
use Illuminate\Http\Request;

class WhatsappTemplateController extends Controller
{
    public function index()
    {
        return view('admin.whatsapp-templates', ['templates' => WhatsappTemplate::whereNull('tenant_id')->orderBy('id')->get()]);
    }

    public function update(Request $request, WhatsappTemplate $template)
    {
        abort_unless($template->tenant_id === null, 404);
        $data = $request->validate(['name' => ['required', 'string', 'max:100'], 'body' => ['required', 'string', 'max:1000'], 'active' => ['required', 'boolean']]);
        preg_match_all('/\{\{([a-z_]+)\}\}/', $data['body'], $matches);
        abort_if(array_diff($matches[1] ?? [], ['customer_name', 'order_number', 'venue_name', 'order_total']) !== [], 422, 'O modelo contém variáveis não permitidas.');
        $template->update($data);
        return back()->with('status', 'Modelo global atualizado.');
    }
}
