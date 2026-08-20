<x-app-layout>
    <x-slot name="header"><h2 class="text-xl font-semibold">Administração da plataforma</h2></x-slot>
    <div class="mx-auto max-w-7xl py-8 sm:px-6 lg:px-8">
        <div class="rounded-2xl bg-slate-900 p-7 text-white"><p class="text-sm font-bold text-emerald-300">OWNER</p><h1 class="mt-2 text-3xl font-bold">Estabelecimentos e planos</h1><p class="mt-2 text-slate-300">Gerencie clientes, planos, suspensões e acesso de suporte.</p></div>
        @if(session('status'))<div class="mt-4 rounded bg-emerald-50 p-3 text-emerald-800">{{ session('status') }}</div>@endif
        <div class="mt-6 overflow-x-auto rounded-xl bg-white shadow"><table class="w-full text-left">
            <thead class="bg-slate-50 text-sm"><tr><th class="p-4">Estabelecimento</th><th class="p-4">Endereço</th><th class="p-4">Plano e status</th><th class="p-4">Ações</th></tr></thead>
            <tbody>@forelse($tenants as $tenant)<tr class="border-t">
                <td class="p-4 font-semibold">{{ $tenant->name }}<small class="block text-slate-500">Cadastrado em {{ $tenant->created_at->format('d/m/Y') }}</small></td>
                <td class="p-4 text-slate-500">{{ $tenant->venues->first()?->subdomain }}.cardapiodigitalbh.com.br</td>
                <td class="p-4"><form class="flex flex-wrap gap-2" method="post" action="{{ route('admin.plan.update', $tenant) }}">@csrf @method('PATCH')<select name="plan" class="rounded border-slate-300 text-sm"><option value="free" @selected($tenant->plan === 'free')>Grátis</option><option value="essencial" @selected($tenant->plan === 'essencial')>Essencial</option><option value="pro" @selected($tenant->plan === 'pro')>Pro</option></select><select name="status" class="rounded border-slate-300 text-sm"><option value="active" @selected($tenant->status === 'active')>Ativo</option><option value="suspended" @selected($tenant->status === 'suspended')>Suspenso</option></select><button class="rounded bg-emerald-600 px-3 py-2 text-sm font-semibold text-white">Salvar</button></form></td>
                <td class="p-4"><form method="post" action="{{ route('admin.impersonate', $tenant) }}">@csrf<button class="rounded bg-slate-900 px-3 py-2 text-sm text-white">Acessar como cliente</button></form></td>
            </tr>@empty<tr><td class="p-6 text-slate-500" colspan="4">Nenhum estabelecimento cadastrado.</td></tr>@endforelse</tbody>
        </table></div>
    </div>
</x-app-layout>
