<x-app-layout>
    <x-slot name="header"><div><h2 class="text-xl font-bold">Operação</h2><p class="text-sm text-slate-500">Salão, cozinha e caixa em um só lugar.</p></div></x-slot>
    <div class="py-8"><div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
        <header class="rounded-3xl bg-gradient-to-br from-slate-900 to-emerald-900 p-7 text-white shadow-xl">
            <p class="text-xs font-bold uppercase tracking-widest text-emerald-300">Operação do salão</p><h1 class="mt-2 text-3xl font-bold">{{ $venue->name }}</h1><p class="mt-2 text-slate-300">Acompanhe mesas, pedidos e recebimentos sem perder o ritmo.</p>
            <div class="mt-6 flex flex-wrap gap-3"><a href="{{ route('kds.index') }}" class="rounded-xl bg-white px-5 py-3 font-bold text-slate-900">Abrir cozinha</a><a href="{{ route('cashier.index') }}" class="rounded-xl border border-white/30 px-5 py-3 font-bold text-white">Abrir caixa</a></div>
        </header>
        <section class="grid gap-4 sm:grid-cols-3">
            <article class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200"><span class="text-sm text-slate-500">Mesas ocupadas</span><strong class="mt-2 block text-3xl">{{ $occupiedTablesCount }}</strong></article>
            <article class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200"><span class="text-sm text-slate-500">Comandas abertas</span><strong class="mt-2 block text-3xl">{{ $openTabsCount }}</strong></article>
            <article class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200"><span class="text-sm text-slate-500">Vendas de hoje</span><strong class="mt-2 block text-3xl">R$ {{ number_format((float) $todaySales, 2, ',', '.') }}</strong></article>
        </section>
        <section><div class="mb-3 flex items-center justify-between"><h2 class="text-xl font-bold">Mapa de mesas</h2><span class="text-xs text-slate-500">Atualize a página para sincronizar</span></div>
            <div class="grid grid-cols-2 gap-4 md:grid-cols-3 xl:grid-cols-5">
                @forelse($tables as $table)
                    <article class="rounded-2xl border-l-4 {{ $table->status === 'occupied' ? 'border-amber-400' : 'border-emerald-500' }} bg-white p-5 shadow-sm ring-1 ring-slate-200"><div class="flex items-start justify-between gap-2"><h3 class="font-bold">Mesa {{ $table->code }}</h3><span class="rounded-full px-2 py-1 text-[11px] font-bold {{ $table->status === 'occupied' ? 'bg-amber-50 text-amber-700' : 'bg-emerald-50 text-emerald-700' }}">{{ $table->status === 'occupied' ? 'Ocupada' : 'Livre' }}</span></div><p class="mt-5 text-xs text-slate-500">{{ $table->tabs->first()?->code ? 'Comanda '.$table->tabs->first()->code : 'Sem comanda aberta' }}</p></article>
                @empty<div class="col-span-full rounded-2xl border border-dashed border-slate-300 bg-white p-10 text-center text-slate-500">Nenhuma mesa cadastrada.</div>@endforelse
            </div>
        </section>
    </div></div>
</x-app-layout>
