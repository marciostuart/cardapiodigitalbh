<x-app-layout>
    <x-slot name="header"><div class="flex items-center justify-between"><div><h2 class="text-xl font-bold">Caixa</h2><p class="text-sm text-slate-500">Comandas abertas e recebimentos.</p></div><a href="{{ route('operations.index') }}" class="text-sm font-semibold text-emerald-700">Mapa de mesas</a></div></x-slot>
    <div class="py-8"><div class="mx-auto max-w-6xl space-y-5 px-4 sm:px-6 lg:px-8">
        @if(session('status'))<div class="rounded-xl bg-emerald-50 p-3 text-emerald-800">{{ session('status') }}</div>@endif
        @if($errors->any())<div class="rounded-xl bg-red-50 p-3 text-red-800">{{ $errors->first() }}</div>@endif
        @forelse($tabs as $tab)
            @php($total = (float) $tab->orders->where('status', '!=', 'cancelled')->sum('total'))
            @php($paid = (float) $tab->payments->where('status', 'confirmed')->sum('amount'))
            @php($balance = max(0, $total - $paid))
            <article class="grid gap-5 rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200 md:grid-cols-[1fr_auto] md:items-center">
                <div><p class="text-sm font-semibold text-emerald-700">{{ $tab->diningTable ? 'Mesa '.$tab->diningTable->code : 'Comanda' }}{{ $tab->customer_name ? ' · '.$tab->customer_name : '' }}</p><h3 class="mt-1 text-xl font-bold">{{ $tab->code }}</h3><div class="mt-4 flex gap-6 text-sm text-slate-500"><span>Total <b class="block text-lg text-slate-900">R$ {{ number_format($total,2,',','.') }}</b></span><span>Pago <b class="block text-lg text-slate-900">R$ {{ number_format($paid,2,',','.') }}</b></span><span>Saldo <b class="block text-lg text-emerald-700">R$ {{ number_format($balance,2,',','.') }}</b></span></div></div>
                <form method="post" action="{{ route('cashier.pay', $tab) }}" class="grid min-w-72 gap-2">@csrf
                    <input name="amount" type="number" value="{{ number_format($balance,2,'.','') }}" min="0.01" max="{{ $balance }}" step="0.01" class="rounded-xl border-slate-300" required>
                    <select name="method" class="rounded-xl border-slate-300"><option value="pix_external">PIX</option><option value="credit">Cartão de crédito</option><option value="debit">Cartão de débito</option><option value="cash">Dinheiro</option><option value="voucher">Vale</option></select>
                    <button class="rounded-xl bg-emerald-600 px-4 py-2.5 font-semibold text-white">Registrar pagamento</button>
                </form>
            </article>
        @empty
            <div class="rounded-2xl border border-dashed border-slate-300 bg-white p-12 text-center text-slate-500">Nenhuma comanda aberta.</div>
        @endforelse
    </div></div>
    <script>setInterval(() => { if (!document.hidden && !['SELECT', 'INPUT'].includes(document.activeElement?.tagName)) window.location.reload(); }, 20000);</script>
</x-app-layout>
