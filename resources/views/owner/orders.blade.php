<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-xl font-bold">Pedidos</h2>
            <p class="text-sm text-slate-500">Mesa, retirada e entrega em uma única fila.</p>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-5 px-4 sm:px-6 lg:px-8">
            @if(session('status'))<div class="rounded-xl bg-emerald-50 p-4 text-emerald-900">{{ session('status') }}</div>@endif
            @if($errors->any())<div class="rounded-xl bg-red-50 p-4 text-red-900">{{ $errors->first() }}</div>@endif

            <form class="flex gap-2">
                <select name="status" class="rounded-xl border-slate-300">
                    <option value="">Todos os status</option>
                    @foreach(['pending','accepted','preparing','ready','out_for_delivery','completed','cancelled'] as $status)
                        <option value="{{ $status }}" @selected(request('status') === $status)>{{ trans('status.order.'.$status) }}</option>
                    @endforeach
                </select>
                <button class="rounded-xl bg-slate-900 px-4 font-bold text-white">Filtrar</button>
            </form>

            <div class="grid gap-4">
                @foreach($orders as $order)
                    @php
                        $primaryActions = match ($order->status) {
                            'pending' => ['accepted' => 'Aceitar'],
                            'accepted' => ['preparing' => 'Em produção'],
                            'preparing' => ['ready' => 'Pedido pronto'],
                            'ready' => ['out_for_delivery' => 'Saiu para entrega', 'completed' => 'Concluído'],
                            'out_for_delivery' => ['completed' => 'Concluído'],
                            default => [],
                        };
                        $canCancel = in_array('cancelled', $allowedTransitions[$order->status] ?? [], true);
                        $cancelLabel = $order->status === 'pending' ? 'Recusar pedido' : 'Cancelar pedido';
                    @endphp

                    <article class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
                        <div class="flex flex-wrap justify-between gap-3">
                            <div>
                                <h2 class="text-lg font-bold">#{{ $order->id }} · {{ $order->customer_name ?: $order->tab?->customer_name ?: 'Mesa' }}</h2>
                                <p class="text-sm text-slate-500">{{ trans('status.channel.'.$order->channel) }} · {{ $order->created_at->format('d/m H:i') }} · {{ trans('status.payment.'.$order->payment_method) }}</p>
                            </div>
                            <div class="text-right">
                                <span class="rounded-full bg-slate-100 px-3 py-1 text-sm font-bold">{{ trans('status.order.'.$order->status) }}</span>
                                <strong class="mt-2 block">R$ {{ number_format((float) $order->total, 2, ',', '.') }}</strong>
                            </div>
                        </div>

                        <div class="mt-3 text-sm text-slate-700">{{ $order->items->map(fn ($item) => $item->quantity.'× '.$item->name_snapshot)->join(' · ') }}</div>

                        <div class="mt-5 flex flex-wrap gap-2">
                            <a href="{{ route('owner.orders.show', $order) }}" class="rounded-xl border border-slate-300 bg-white px-4 py-2.5 font-bold text-slate-800 transition hover:bg-slate-50">Detalhes</a>
                        @if($primaryActions || $canCancel)
                                @foreach($primaryActions as $nextStatus => $label)
                                    <form method="post" action="{{ route('owner.orders.update', $order) }}">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="status" value="{{ $nextStatus }}">
                                        <button class="rounded-xl bg-emerald-600 px-4 py-2.5 font-bold text-white transition hover:bg-emerald-700">{{ $label }}</button>
                                    </form>
                                @endforeach

                                @if($canCancel)
                                    <form id="cancel-order-{{ $order->id }}" method="post" action="{{ route('owner.orders.update', $order) }}" class="hidden">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="status" value="cancelled">
                                    </form>
                                    <button type="button" data-cancel-form="cancel-order-{{ $order->id }}" data-order-label="#{{ $order->id }}" data-cancel-label="{{ $cancelLabel }}" class="rounded-xl border border-red-200 bg-red-50 px-4 py-2.5 font-bold text-red-700 transition hover:bg-red-100">{{ $cancelLabel }}</button>
                                @endif
                        @endif
                        </div>
                    </article>
                @endforeach
            </div>

            {{ $orders->links() }}
        </div>
    </div>

    <dialog id="cancelOrderDialog" class="w-[calc(100%-32px)] max-w-md rounded-2xl border-0 p-0 shadow-2xl backdrop:bg-slate-950/60">
        <div class="p-6">
            <p class="text-sm font-bold uppercase tracking-wide text-red-600">Confirmação necessária</p>
            <h2 id="cancelOrderTitle" class="mt-2 text-xl font-bold text-slate-900">Cancelar pedido?</h2>
            <p id="cancelOrderDescription" class="mt-3 text-sm leading-6 text-slate-600">Esta ação cancela o pedido e não poderá ser desfeita.</p>
            <div class="mt-6 flex justify-end gap-3">
                <button id="cancelOrderDismiss" type="button" class="rounded-xl border border-slate-300 px-4 py-2.5 font-bold text-slate-700">Voltar</button>
                <button id="cancelOrderConfirm" type="button" class="rounded-xl bg-red-600 px-4 py-2.5 font-bold text-white hover:bg-red-700">Confirmar cancelamento</button>
            </div>
        </div>
    </dialog>

    <script>
        (() => {
            const dialog = document.getElementById('cancelOrderDialog');
            const confirmButton = document.getElementById('cancelOrderConfirm');
            let cancellationForm = null;

            document.querySelectorAll('[data-cancel-form]').forEach(button => button.addEventListener('click', () => {
                cancellationForm = document.getElementById(button.dataset.cancelForm);
                const action = button.dataset.cancelLabel;
                document.getElementById('cancelOrderTitle').textContent = `${action} ${button.dataset.orderLabel}?`;
                document.getElementById('cancelOrderDescription').textContent = 'Esta ação não poderá ser desfeita. Confirme somente se você tiver certeza.';
                confirmButton.textContent = `Confirmar: ${action}`;
                dialog.showModal();
            }));

            document.getElementById('cancelOrderDismiss').addEventListener('click', () => dialog.close());
            dialog.addEventListener('click', event => { if (event.target === dialog) dialog.close(); });
            confirmButton.addEventListener('click', () => cancellationForm?.requestSubmit());
        })();
    </script>
</x-app-layout>
