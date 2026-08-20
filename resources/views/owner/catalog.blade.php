<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
            <div><h2 class="text-xl font-bold text-slate-900">Cardápio</h2><p class="text-sm text-slate-500">Organize categorias, produtos, preços e disponibilidade.</p></div>
            <a href="https://{{ $venue->subdomain }}.cardapiodigitalbh.com.br" target="_blank" rel="noopener" class="text-sm font-semibold text-emerald-700">Abrir cardápio público →</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            @if(session('status'))<div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">{{ session('status') }}</div>@endif
            @if($errors->any())<div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{{ $errors->first() }}</div>@endif

            <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-900">
                <strong>Ordenação atual:</strong> {{ $venue->catalog_sort_mode === 'popularity' ? 'popularidade por categoria' : 'manual' }}.
                <a href="{{ route('owner.settings') }}" class="ml-1 font-bold underline">Alterar nas configurações</a>
            </div>

            <div class="grid gap-6 lg:grid-cols-2">
                <form method="post" action="{{ route('owner.categories.store') }}" class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                    @csrf
                    <h3 class="text-lg font-bold text-slate-900">Nova categoria</h3>
                    <p class="mt-1 text-sm text-slate-500">Ex.: Hambúrgueres, bebidas ou sobremesas.</p>
                    <label class="mt-4 block text-sm font-semibold text-slate-700">Nome</label>
                    <input name="name" value="{{ old('name') }}" class="mt-2 w-full rounded-xl border-slate-300 focus:border-emerald-500 focus:ring-emerald-500" required maxlength="80">
                    <button class="mt-4 rounded-xl bg-slate-900 px-5 py-2.5 font-semibold text-white hover:bg-slate-700">Adicionar categoria</button>
                </form>

                <form method="post" action="{{ route('owner.products.store') }}" class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                    @csrf
                    <h3 class="text-lg font-bold text-slate-900">Novo produto</h3>
                    @if($categories->isEmpty())
                        <p class="mt-3 rounded-xl bg-amber-50 p-4 text-sm text-amber-800">Crie sua primeira categoria para liberar o cadastro de produtos.</p>
                    @else
                        <div class="mt-4 grid gap-3 sm:grid-cols-2">
                            <select name="product_category_id" class="rounded-xl border-slate-300" required>@foreach($categories as $category)<option value="{{ $category->id }}">{{ $category->name }}</option>@endforeach</select>
                            <input name="name" class="rounded-xl border-slate-300" placeholder="Nome do produto" required maxlength="120">
                            <input name="sku" class="rounded-xl border-slate-300" placeholder="Código/SKU opcional" maxlength="60">
                            <input name="price" type="number" step="0.01" min="0" class="rounded-xl border-slate-300" placeholder="Preço" required>
                            <input name="position" type="number" min="0" step="1" class="rounded-xl border-slate-300" placeholder="Ordem manual (opcional)">
                            <select name="station" class="rounded-xl border-slate-300"><option value="kitchen">Cozinha</option><option value="bar">Bar</option></select>
                            <textarea name="description" class="rounded-xl border-slate-300 sm:col-span-2" rows="2" maxlength="1000" placeholder="Descrição opcional"></textarea>
                            <label class="flex items-center gap-2 text-sm"><input type="hidden" name="track_stock" value="0"><input type="checkbox" name="track_stock" value="1"> Controlar estoque do produto</label>
                            <input name="stock_on_hand" type="number" min="0" step="0.001" placeholder="Estoque atual" class="rounded-xl border-slate-300">
                            <input name="low_stock_threshold" type="number" min="0" step="0.001" placeholder="Alerta mínimo" class="rounded-xl border-slate-300">
                        </div>
                        <button class="mt-4 rounded-xl bg-emerald-600 px-5 py-2.5 font-semibold text-white hover:bg-emerald-700">Cadastrar produto</button>
                    @endif
                </form>
            </div>

            <section class="space-y-5">
                @forelse($categories as $category)
                    <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
                        <form method="post" action="{{ route('owner.categories.update', $category) }}" class="flex flex-col gap-3 border-b border-slate-100 pb-4 sm:flex-row sm:items-center">
                            @csrf @method('patch')
                            <input name="name" value="{{ $category->name }}" class="min-w-0 flex-1 rounded-xl border-slate-300 font-bold" required>
                            <select name="active" class="rounded-xl border-slate-300"><option value="1" @selected($category->active)>Visível</option><option value="0" @selected(!$category->active)>Oculta</option></select>
                            <button class="rounded-xl border border-slate-300 px-4 py-2 font-semibold text-slate-700">Salvar categoria</button>
                        </form>

                        <div class="mt-4 space-y-3">
                            @forelse($category->products as $product)
                                <form method="post" action="{{ route('owner.products.update', $product) }}" class="grid gap-3 rounded-xl bg-slate-50 p-4 md:grid-cols-12 md:items-center">
                                    @csrf @method('patch')
                                    <select name="product_category_id" class="rounded-lg border-slate-300 md:col-span-2">@foreach($categories as $option)<option value="{{ $option->id }}" @selected($option->id === $product->product_category_id)>{{ $option->name }}</option>@endforeach</select>
                                    <input name="name" value="{{ $product->name }}" class="rounded-lg border-slate-300 md:col-span-2" required>
                                    <input name="sku" value="{{ $product->sku }}" class="rounded-lg border-slate-300 md:col-span-1" placeholder="SKU">
                                    <input name="description" value="{{ $product->description }}" class="rounded-lg border-slate-300 md:col-span-3" placeholder="Descrição">
                                    <input name="price" value="{{ $product->price }}" type="number" step="0.01" min="0" class="rounded-lg border-slate-300 md:col-span-1" required>
                                    <input name="position" value="{{ $product->position }}" type="number" min="0" step="1" title="Ordem manual" class="rounded-lg border-slate-300 md:col-span-1" required>
                                    <select name="station" class="rounded-lg border-slate-300 md:col-span-1"><option value="kitchen" @selected($product->station === 'kitchen')>Cozinha</option><option value="bar" @selected($product->station === 'bar')>Bar</option></select>
                                    <select name="active" class="rounded-lg border-slate-300 md:col-span-1"><option value="1" @selected($product->active)>Ativo</option><option value="0" @selected(!$product->active)>Pausado</option></select>
                                    <label class="flex items-center gap-1 text-xs md:col-span-1"><input type="hidden" name="track_stock" value="0"><input type="checkbox" name="track_stock" value="1" @checked($product->track_stock)> Estoque</label>
                                    <input name="stock_on_hand" value="{{ $product->stock_on_hand }}" type="number" min="0" step="0.001" title="Estoque atual" class="rounded-lg border-slate-300 md:col-span-1">
                                    <input name="low_stock_threshold" value="{{ $product->low_stock_threshold }}" type="number" min="0" step="0.001" title="Alerta mínimo" class="rounded-lg border-slate-300 md:col-span-1">
                                    <button class="rounded-lg bg-slate-900 px-3 py-2 text-sm font-semibold text-white md:col-span-1">Salvar produto</button>
                                </form>
                            @empty
                                <p class="py-4 text-sm text-slate-500">Nenhum produto nesta categoria.</p>
                            @endforelse
                        </div>
                    </div>
                @empty
                    <div class="rounded-2xl border border-dashed border-slate-300 bg-white p-10 text-center text-slate-500">Seu cardápio ainda está vazio. Comece criando uma categoria.</div>
                @endforelse
            </section>
        </div>
    </div>
</x-app-layout>
