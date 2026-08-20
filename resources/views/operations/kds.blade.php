<x-app-layout>
    <x-slot name="header"><div class="flex items-center justify-between"><div><h2 class="text-xl font-bold">Cozinha</h2><p class="text-sm text-slate-500">Pedidos em preparo de {{ $venue->name }}</p></div><a href="{{ route('operations.index') }}" class="text-sm font-semibold text-emerald-700">Mapa de mesas</a></div></x-slot>
    <div class="py-8"><div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        @if(session('status'))<div class="mb-5 rounded-xl bg-emerald-50 p-3 text-emerald-800">{{ session('status') }}</div>@endif
        <div class="grid gap-5 md:grid-cols-2 xl:grid-cols-3">
            @forelse($orders as $order)
                <article class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">
                    <header class="flex items-center justify-between bg-slate-900 px-5 py-4 text-white"><div><b>Pedido #{{ $order->id }}</b><p class="text-xs text-slate-300">{{ $order->tab?->diningTable ? 'Mesa '.$order->tab->diningTable->code : $order->tab?->code }}</p></div><span class="rounded-full bg-white/10 px-3 py-1 text-xs">{{ $order->created_at->diffForHumans() }}</span></header>
                    <div class="divide-y divide-slate-100 p-4">
                        @foreach($order->items as $item)
                            <form method="post" action="{{ route('kds.items.update', $item) }}" class="py-3">@csrf @method('patch')
                                <div class="flex justify-between gap-3"><div><b>{{ $item->quantity }}× {{ $item->name_snapshot }}</b><p class="text-xs text-slate-500">{{ $item->station === 'bar' ? 'Bar' : 'Cozinha' }} · {{ $item->status }}</p></div>
                                    <select name="status" onchange="this.form.submit()" class="rounded-lg border-slate-300 text-sm"><option value="accepted" @selected($item->status === 'accepted' || $item->status === 'pending')>Aceito</option><option value="preparing" @selected($item->status === 'preparing')>Preparando</option><option value="ready" @selected($item->status === 'ready')>Pronto</option><option value="cancelled" @selected($item->status === 'cancelled')>Cancelado</option></select>
                                </div>
                            </form>
                        @endforeach
                    </div>
                </article>
            @empty
                <div class="rounded-2xl border border-dashed border-slate-300 bg-white p-12 text-center text-slate-500 md:col-span-2 xl:col-span-3">Nenhum pedido aguardando preparo.</div>
            @endforelse
        </div>
    </div></div>
</x-app-layout>
