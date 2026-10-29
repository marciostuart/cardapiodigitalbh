<x-app-layout>
    <x-slot name="header"><div><h2 class="text-xl font-bold">Mesas e QR Codes</h2><p class="text-sm text-slate-500">Crie mesas e imprima o acesso individual ao cardápio.</p></div></x-slot>
    <div class="py-8"><div class="mx-auto max-w-6xl space-y-6 px-4 sm:px-6 lg:px-8">
        @if(session('status'))<div class="rounded-xl bg-emerald-50 p-3 text-emerald-800">{{ session('status') }}</div>@endif
        @if($errors->any())<div class="rounded-xl bg-red-50 p-3 text-red-800">{{ $errors->first() }}</div>@endif
        @if(session('geofence_access_code'))<div class="rounded-xl border border-amber-300 bg-amber-50 p-4 text-amber-950"><p class="font-bold">Código temporário da Mesa {{ request()->session()->get('geofence_access_code_table') ?: '' }}</p><p class="mt-1 text-sm">Válido até {{ session('geofence_access_code_expires_at') }} e aceita um pedido. Passe o código somente ao cliente desta mesa.</p><p class="mt-2 font-mono text-3xl font-black tracking-[0.25em]">{{ session('geofence_access_code') }}</p></div>@endif
        <form method="post" action="{{ route('owner.tables.store') }}" class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200">@csrf
            <h3 class="text-lg font-bold">Cadastrar mesa</h3><p class="mt-1 text-sm text-slate-500">Cada mesa recebe um QR Code exclusivo e permanente.</p>
            <div class="mt-4 grid gap-3 sm:grid-cols-[1fr_2fr_auto]"><input name="code" class="rounded-xl border-slate-300" placeholder="Número ou código" required maxlength="30"><input name="name" class="rounded-xl border-slate-300" placeholder="Nome opcional (ex.: Varanda)"><button class="rounded-xl bg-slate-900 px-5 py-2.5 font-semibold text-white">Cadastrar mesa</button></div>
        </form>
        <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
            @forelse($tables as $table)
                <article class="qr-card rounded-2xl bg-white p-5 text-center shadow-sm ring-1 ring-slate-200">
                    <p class="text-xs font-bold uppercase tracking-widest text-emerald-700">{{ $venue->name }}</p><h3 class="mt-1 text-2xl font-black">Mesa {{ $table->code }}</h3>@if($table->name)<p class="text-sm text-slate-500">{{ $table->name }}</p>@endif
                    <img src="{{ route('owner.tables.qr', $table) }}" alt="QR Code da mesa {{ $table->code }}" class="mx-auto mt-4 w-full max-w-56">
                    <p class="mt-3 break-all text-xs text-slate-400">{{ route('public.qr-menu', $table->qr_token) }}</p>
                    <div class="no-print mt-4 flex justify-center gap-2"><a href="{{ route('public.qr-menu', $table->qr_token) }}" target="_blank" rel="noopener" class="rounded-lg border border-slate-300 px-3 py-2 text-sm font-semibold">Testar</a><button type="button" onclick="printCard(this)" class="rounded-lg bg-emerald-600 px-3 py-2 text-sm font-semibold text-white">Imprimir</button></div>
                    @if($venue->geofence_enabled)<form method="post" action="{{ route('owner.tables.geofence-override', $table) }}" class="no-print mt-3 border-t border-slate-200 pt-3">@csrf<button class="rounded-lg bg-amber-100 px-3 py-2 text-sm font-semibold text-amber-950">Gerar código temporário</button><p class="mt-1 text-xs text-slate-500">Invalida o anterior; permite um pedido e expira em 15 minutos.</p></form>@endif
                </article>
            @empty<div class="col-span-full rounded-2xl border border-dashed border-slate-300 bg-white p-12 text-center text-slate-500">Nenhuma mesa cadastrada.</div>@endforelse
        </div>
    </div></div>
    <style>@media print{body *{visibility:hidden}.qr-card.printing,.qr-card.printing *{visibility:visible}.qr-card.printing{position:absolute;inset:0;margin:auto;width:420px;height:max-content;box-shadow:none}.no-print{display:none!important}}</style>
    <script>function printCard(button){const card=button.closest('.qr-card');card.classList.add('printing');window.print();card.classList.remove('printing')}</script>
</x-app-layout>
