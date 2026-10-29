<x-app-layout>
    <x-slot name="header"><div><h2 class="text-xl font-bold">Impressoras</h2><p class="text-sm text-slate-500">Conecte um computador Windows com uma impressora térmica USB.</p></div></x-slot>

    <div class="mx-auto max-w-5xl space-y-5 px-4 py-6 sm:px-6 lg:px-8">
        @if(session('status'))<div role="status" class="rounded-xl bg-emerald-50 p-4 text-sm font-semibold text-emerald-800">{{ session('status') }}</div>@endif
        @if(session('printer_pairing_code'))<section role="status" class="rounded-2xl border border-amber-300 bg-amber-50 p-5"><h3 class="font-bold text-amber-950">Código para conectar o computador</h3><p class="mt-2 font-mono text-3xl font-black tracking-[.25em] text-amber-950">{{ session('printer_pairing_code') }}</p><p class="mt-2 text-sm text-amber-900">Use este código uma vez no aplicativo Cardápio Digital BH do computador. Ele expira em {{ session('printer_pairing_expires') }}.</p></section>@endif
        @if($errors->any())<div role="alert" class="rounded-xl bg-rose-50 p-4 text-sm font-semibold text-rose-800">{{ $errors->first() }}</div>@endif

        <section class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
            <h3 class="text-lg font-bold">Computadores conectados</h3>
            <p class="mt-1 text-sm text-slate-600">O computador precisa permanecer ligado e conectado à internet. A impressora é escolhida no Windows; instale o driver do fabricante antes de iniciar o agente.</p>
            <form method="post" action="{{ route('owner.printers.pair') }}" class="mt-4">@csrf<button class="rounded-lg bg-slate-900 px-5 py-3 text-sm font-bold text-white">Gerar código de pareamento</button></form>
            <div class="mt-5 space-y-3">
                @forelse($devices as $device)
                    <article class="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-slate-200 p-4">
                        <div><strong>{{ $device->name }}</strong>@if($device->is_primary)<span class="ml-2 rounded-full bg-emerald-100 px-2 py-1 text-xs font-bold text-emerald-800">principal</span>@endif<p class="mt-1 text-xs text-slate-500">@if(!$device->active)Acesso revogado@else{{ $device->last_seen_at && $device->last_seen_at->greaterThan(now()->subMinutes(2)) ? 'Conectado agora' : 'Último contato: '.($device->last_seen_at?->format('d/m/Y H:i') ?? 'nunca') }}@endif</p></div>
                        @if($device->active)<div class="flex gap-2">@unless($device->is_primary)<form method="post" action="{{ route('owner.printers.primary', $device) }}">@csrf @method('PATCH')<button class="rounded-lg border border-slate-300 px-3 py-2 text-sm font-semibold">Definir principal</button></form>@endunless<form method="post" action="{{ route('owner.printers.revoke', $device) }}" onsubmit="return confirm('Revogar o acesso deste computador?');">@csrf @method('DELETE')<button class="rounded-lg border border-rose-200 px-3 py-2 text-sm font-semibold text-rose-700">Revogar</button></form></div>@endif
                    </article>
                @empty<p class="rounded-xl border border-dashed border-slate-300 p-4 text-sm text-slate-500">Nenhum computador foi conectado ainda.</p>@endforelse
            </div>
        </section>

        <section class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
            <h3 class="text-lg font-bold">Trabalhos recentes</h3>
            <div class="mt-3 divide-y divide-slate-100">@forelse($jobs as $job)<div class="flex justify-between gap-3 py-3 text-sm"><span>Pedido #{{ $job->order_id }} · {{ $job->created_at->format('d/m H:i') }}</span><strong>{{ ['queued' => 'Na fila', 'printing' => 'Imprimindo', 'printed' => 'Impresso', 'failed' => 'Falhou'][$job->status] ?? $job->status }}</strong></div>@empty<p class="text-sm text-slate-500">Nenhum trabalho de impressão.</p>@endforelse</div>
        </section>
    </div>
</x-app-layout>
