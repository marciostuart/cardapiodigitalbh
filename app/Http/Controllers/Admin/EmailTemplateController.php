<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EmailTemplate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EmailTemplateController extends Controller
{
    public function index(): View
    {
        return view('admin.email-templates', [
            'templates' => EmailTemplate::orderBy('id')->get(),
        ]);
    }

    public function update(Request $request, EmailTemplate $template): RedirectResponse
    {
        $data = $request->validate([
            'subject' => ['required', 'string', 'max:180'],
            'heading' => ['required', 'string', 'max:180'],
            'body' => ['required', 'string', 'max:5000'],
            'action_text' => ['nullable', 'string', 'max:80'],
            'footer' => ['nullable', 'string', 'max:500'],
            'active' => ['required', 'boolean'],
        ]);

        $template->update($data);

        return back()->with('status', 'Template de e-mail atualizado.');
    }
}
