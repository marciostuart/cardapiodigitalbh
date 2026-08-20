<x-app-layout>
    <x-slot name="header"><div><h2 class="text-xl font-bold">Configurações</h2><p class="text-sm text-slate-500">Identidade e preferências do estabelecimento.</p></div></x-slot>
    <div class="py-8"><div class="mx-auto max-w-3xl space-y-6 px-4 sm:px-6 lg:px-8">
        @if(session('status'))<div class="rounded-xl bg-emerald-50 p-3 text-emerald-800">{{ session('status') }}</div>@endif
        @if($errors->any())<div class="rounded-xl bg-red-50 p-3 text-red-800">{{ $errors->first() }}</div>@endif
        <section class="rounded-2xl bg-slate-900 p-6 text-white"><p class="text-xs font-bold uppercase tracking-widest text-emerald-300">Plano atual</p><div class="mt-2 flex items-end justify-between"><h2 class="text-2xl font-bold">{{ ucfirst($tenant->plan) }}</h2><span class="rounded-full bg-white/10 px-3 py-1 text-xs">{{ $tenant->status === 'active' ? 'Ativo' : 'Suspenso' }}</span></div></section>
        <form method="post" action="{{ route('owner.settings.update') }}" class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200">@csrf @method('patch')
            <label class="block text-sm font-semibold text-slate-700">Nome público</label><input name="name" value="{{ old('name', $venue->name) }}" class="mt-2 w-full rounded-xl border-slate-300" required>
            <label class="mt-5 block text-sm font-semibold text-slate-700">Endereço do cardápio</label><div class="mt-2 rounded-xl bg-slate-100 px-4 py-3 text-sm text-slate-700">https://{{ $venue->subdomain }}.cardapiodigitalbh.com.br</div><p class="mt-1 text-xs text-slate-500">O endereço é permanente para não quebrar QR Codes e links já divulgados.</p>
            <label class="mt-5 block text-sm font-semibold text-slate-700">Fuso horário</label><select name="timezone" class="mt-2 w-full rounded-xl border-slate-300"><option value="America/Sao_Paulo" @selected($venue->timezone === 'America/Sao_Paulo')>Brasília</option><option value="America/Manaus" @selected($venue->timezone === 'America/Manaus')>Manaus</option><option value="America/Cuiaba" @selected($venue->timezone === 'America/Cuiaba')>Cuiabá</option><option value="America/Belem" @selected($venue->timezone === 'America/Belem')>Belém</option><option value="America/Fortaleza" @selected($venue->timezone === 'America/Fortaleza')>Fortaleza</option><option value="America/Recife" @selected($venue->timezone === 'America/Recife')>Recife</option></select>
            <button class="mt-6 rounded-xl bg-emerald-600 px-5 py-2.5 font-semibold text-white">Salvar configurações</button>
        </form>
    </div></div>
</x-app-layout>
