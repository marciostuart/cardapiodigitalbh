<x-app-layout>
    <x-slot name="header"><div><h2 class="text-xl font-bold">Pagamentos online</h2><p class="text-sm text-slate-500">Conecte a conta do Mercado Pago do estabelecimento.</p></div></x-slot>

    <div class="mx-auto max-w-4xl space-y-5 px-4 py-6 sm:px-6 lg:px-8">
        @if(session('status'))<div role="status" class="rounded-xl bg-emerald-50 p-4 text-sm font-semibold text-emerald-800">{{ session('status') }}</div>@endif
        @if($errors->any())<div role="alert" class="rounded-xl bg-rose-50 p-4 text-sm font-semibold text-rose-800">{{ $errors->first() }}</div>@endif

        <section class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
            <h3 class="text-lg font-bold text-slate-900">Mercado Pago</h3>
            @if(!$configured)
                <p class="mt-3 text-sm leading-6 text-slate-600">A plataforma ainda não terminou a configuração de homologação. Você poderá conectar sua conta depois que essa etapa for liberada.</p>
            @elseif($connection)
                <p class="mt-3 text-sm leading-6 text-slate-600">Conta conectada para receber pagamentos dos pedidos. Identificador da conta: <strong>{{ $connection->seller_id }}</strong>.</p>
                <p class="mt-1 text-xs text-slate-500">Conexão feita em {{ $connection->connected_at?->format('d/m/Y H:i') }}. Os pedidos online ficam aguardando confirmação antes de chegar à cozinha.</p>
                <form method="post" action="{{ route('owner.payments.disconnect') }}" class="mt-5" onsubmit="return confirm('Desconectar o Mercado Pago? Novos pedidos online deixarão de ser aceitos.');">
                    @csrf @method('DELETE')
                    <button class="rounded-lg border border-rose-200 px-4 py-2 text-sm font-semibold text-rose-700">Desconectar conta</button>
                </form>
            @else
                <p class="mt-3 text-sm leading-6 text-slate-600">O cliente faz o pedido, é encaminhado ao checkout seguro do Mercado Pago e volta para acompanhar a confirmação. A equipe só poderá aceitar o pedido depois que o pagamento for aprovado.</p>
                <p class="mt-2 text-xs leading-5 text-slate-500">A conexão usa a autorização oficial do Mercado Pago. Sua senha não é compartilhada com o Cardápio Digital BH.</p>
                <form method="post" action="{{ route('owner.payments.connect') }}" class="mt-5">
                    @csrf
                    <button class="rounded-lg bg-slate-900 px-5 py-3 text-sm font-bold text-white">Conectar minha conta</button>
                </form>
            @endif
        </section>

        <section class="rounded-2xl border border-slate-200 bg-slate-50 p-5 text-sm leading-6 text-slate-600">
            <h3 class="font-bold text-slate-900">Homologação e ativação</h3>
            <p class="mt-2">A plataforma inicia em modo de teste. Antes de receber pagamentos reais, a equipe técnica precisa validar um pedido completo, o QR PIX do checkout, o retorno do Mercado Pago e a confirmação assinada do webhook.</p>
        </section>
    </div>
</x-app-layout>
