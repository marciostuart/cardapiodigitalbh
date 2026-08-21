<x-app-layout>
    <x-slot name="header"><div><h2 class="text-xl font-bold">E-mails transacionais</h2><p class="text-sm text-slate-500">Edite as mensagens enviadas automaticamente pela plataforma.</p></div></x-slot>
    <div class="py-8"><div class="mx-auto max-w-6xl space-y-6 px-4 sm:px-6 lg:px-8">
        <div class="rounded-2xl bg-slate-900 p-6 text-white"><div class="flex flex-wrap items-end justify-between gap-5"><div><h1 class="text-2xl font-bold">Comunicação padronizada</h1><p class="mt-2 text-sm text-slate-300">O visual HTML é responsivo e protegido. Você edita o conteúdo; a identidade visual permanece consistente.</p><p class="mt-3 text-xs text-emerald-300">Variáveis disponíveis: @{{name}}, @{{venue_name}}, @{{app_name}} e @{{expire_minutes}}.</p></div><form method="post" action="{{ route('admin.email-templates.test') }}">@csrf<button class="rounded-xl bg-white px-4 py-3 text-sm font-bold text-slate-900">Enviar teste para meu e-mail</button></form></div></div>
        @if(session('status'))<div class="rounded-xl bg-emerald-50 p-3 text-emerald-800">{{ session('status') }}</div>@endif
        @if($errors->any())<div class="rounded-xl bg-red-50 p-3 text-red-800">{{ $errors->first() }}</div>@endif
        @foreach($templates as $template)
            <form method="post" action="{{ route('admin.email-templates.update', $template) }}" class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                @csrf @method('patch')
                <div class="flex flex-wrap items-start justify-between gap-3"><div><p class="text-xs font-bold uppercase tracking-wider text-emerald-700">{{ $template->key }}</p><h2 class="mt-1 text-xl font-bold">{{ $template->name }}</h2></div><select name="active" class="rounded-xl border-slate-300 text-sm"><option value="1" @selected($template->active)>Ativo</option><option value="0" @selected(! $template->active)>Usar conteúdo padrão</option></select></div>
                <div class="mt-5 grid gap-4 md:grid-cols-2">
                    <label class="text-sm font-semibold text-slate-700">Assunto<input name="subject" value="{{ old('subject', $template->subject) }}" maxlength="180" required class="mt-1 block w-full rounded-xl border-slate-300"></label>
                    <label class="text-sm font-semibold text-slate-700">Título<input name="heading" value="{{ old('heading', $template->heading) }}" maxlength="180" required class="mt-1 block w-full rounded-xl border-slate-300"></label>
                    <label class="text-sm font-semibold text-slate-700 md:col-span-2">Mensagem<textarea name="body" rows="5" maxlength="5000" required class="mt-1 block w-full rounded-xl border-slate-300">{{ old('body', $template->body) }}</textarea></label>
                    <label class="text-sm font-semibold text-slate-700">Texto do botão<input name="action_text" value="{{ old('action_text', $template->action_text) }}" maxlength="80" class="mt-1 block w-full rounded-xl border-slate-300"></label>
                    <label class="text-sm font-semibold text-slate-700">Rodapé<input name="footer" value="{{ old('footer', $template->footer) }}" maxlength="500" class="mt-1 block w-full rounded-xl border-slate-300"></label>
                </div>
                <button class="mt-5 rounded-xl bg-emerald-600 px-5 py-3 font-bold text-white">Salvar modelo</button>
            </form>
        @endforeach
    </div></div>
</x-app-layout>
