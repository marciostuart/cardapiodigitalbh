@php($editing = isset($product))
<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <a href="{{ route('owner.catalog') }}" class="text-sm font-semibold text-emerald-700">&larr; Voltar ao cardápio</a>
                <h2 class="mt-1 text-xl font-bold text-slate-900">{{ $editing ? 'Editar produto' : 'Cadastrar produto' }}</h2>
                <p class="text-sm text-slate-500">Preencha as informações que o cliente verá no cardápio.</p>
            </div>
            <a href="https://{{ $venue->subdomain }}.cardapiodigitalbh.com.br" target="_blank" class="rounded-xl border border-slate-300 bg-white px-4 py-2 text-center text-sm font-bold text-slate-700">Ver cardápio público</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-6xl space-y-6 px-4 sm:px-6 lg:px-8">
            @if(session('status'))
                <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-emerald-900">{{ session('status') }}</div>
            @endif
            @if($errors->any())
                <div class="rounded-xl border border-red-200 bg-red-50 p-4 text-red-900">
                    <p class="font-bold">Revise os campos indicados:</p>
                    <ul class="mt-1 list-inside list-disc text-sm">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                </div>
            @endif

            @if($categories->isEmpty())
                <div class="rounded-2xl border border-amber-300 bg-amber-50 p-6 text-amber-950">
                    <strong>Cadastre uma categoria antes do produto.</strong>
                    <a href="{{ route('owner.catalog') }}" class="ml-2 font-bold underline">Criar categoria</a>
                </div>
            @else
                <form id="productForm" method="post" enctype="multipart/form-data" action="{{ $editing ? route('owner.products.update', $product) : route('owner.products.store') }}" class="space-y-6">
                    @csrf
                    @if($editing) @method('PATCH') @endif

                    <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_340px]">
                        <div class="space-y-6">
                            <section class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200 sm:p-6">
                                <div class="flex items-center gap-3 border-b border-slate-100 pb-4">
                                    <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-slate-900 text-white">🍴</span>
                                    <div><h3 class="font-bold text-slate-900">Informações principais</h3><p class="text-xs text-slate-500">Nome, categoria, descrição e preço.</p></div>
                                </div>
                                <div class="mt-5 grid gap-4 sm:grid-cols-2">
                                    <label class="block text-sm font-semibold text-slate-700">Categoria
                                        <select name="product_category_id" required class="mt-2 w-full rounded-xl border-slate-300">
                                            @foreach($categories as $category)<option value="{{ $category->id }}" @selected((string)old('product_category_id', $product->product_category_id ?? '') === (string)$category->id)>{{ $category->name }}</option>@endforeach
                                        </select>
                                    </label>
                                    <label class="block text-sm font-semibold text-slate-700">Referência / SKU <span class="font-normal text-slate-400">(opcional)</span>
                                        <input name="sku" maxlength="60" value="{{ old('sku', $product->sku ?? '') }}" placeholder="Ex.: BURGER-001" class="mt-2 w-full rounded-xl border-slate-300">
                                    </label>
                                    <label class="block text-sm font-semibold text-slate-700 sm:col-span-2">Nome do produto
                                        <input name="name" maxlength="120" required value="{{ old('name', $product->name ?? '') }}" placeholder="Ex.: Hambúrguer artesanal" class="mt-2 w-full rounded-xl border-slate-300">
                                    </label>
                                    <label class="block text-sm font-semibold text-slate-700 sm:col-span-2">Descrição <span class="font-normal text-slate-400">(opcional)</span>
                                        <textarea name="description" maxlength="1000" rows="5" placeholder="Ingredientes, tamanho e diferenciais do produto" class="mt-2 w-full rounded-xl border-slate-300">{{ old('description', $product->description ?? '') }}</textarea>
                                    </label>
                                    <label class="block text-sm font-semibold text-slate-700">Preço (R$)
                                        <input name="price" type="number" min="0" step="0.01" required value="{{ old('price', $product->price ?? '') }}" placeholder="0,00" class="mt-2 w-full rounded-xl border-slate-300">
                                    </label>
                                    <label class="block text-sm font-semibold text-slate-700">Estação de preparo
                                        <select name="station" class="mt-2 w-full rounded-xl border-slate-300"><option value="kitchen" @selected(old('station', $product->station ?? 'kitchen') === 'kitchen')>Cozinha</option><option value="bar" @selected(old('station', $product->station ?? '') === 'bar')>Bar</option></select>
                                    </label>
                                </div>
                            </section>

                            <section class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200 sm:p-6">
                                <h3 class="font-bold text-slate-900">Estoque e exibição</h3>
                                <div class="mt-4 grid gap-4 sm:grid-cols-3">
                                    <label class="block text-sm font-semibold text-slate-700">Quantidade em estoque
                                        <input name="stock_on_hand" type="number" min="0" step="0.001" value="{{ old('stock_on_hand', $product->stock_on_hand ?? 0) }}" class="mt-2 w-full rounded-xl border-slate-300">
                                    </label>
                                    <label class="block text-sm font-semibold text-slate-700">Avisar estoque baixo
                                        <input name="low_stock_threshold" type="number" min="0" step="0.001" value="{{ old('low_stock_threshold', $product->low_stock_threshold ?? 0) }}" class="mt-2 w-full rounded-xl border-slate-300">
                                    </label>
                                    <label class="block text-sm font-semibold text-slate-700">Ordem manual
                                        <input name="position" type="number" min="0" value="{{ old('position', $product->position ?? 0) }}" class="mt-2 w-full rounded-xl border-slate-300">
                                    </label>
                                </div>
                                <div class="mt-5 flex flex-wrap gap-3">
                                    <label class="flex items-center gap-2 rounded-xl bg-slate-50 px-4 py-3 text-sm font-semibold"><input type="hidden" name="track_stock" value="0"><input type="checkbox" name="track_stock" value="1" @checked(old('track_stock', $product->track_stock ?? false))> Controlar estoque deste produto</label>
                                    @if($editing)<label class="flex items-center gap-2 rounded-xl bg-slate-50 px-4 py-3 text-sm font-semibold"><input type="hidden" name="active" value="0"><input type="checkbox" name="active" value="1" @checked(old('active', $product->active))> Produto ativo e visível</label>@endif
                                </div>
                            </section>
                        </div>

                        <aside class="space-y-6">
                            <section class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
                                <h3 class="font-bold text-slate-900">Foto de destaque</h3>
                                <p class="mt-1 text-xs text-slate-500">Uma foto quadrada ou horizontal apresenta melhor resultado.</p>
                                <label id="dropZone" for="imageInput" class="mt-4 flex min-h-64 cursor-pointer flex-col items-center justify-center overflow-hidden rounded-2xl border-2 border-dashed border-slate-300 bg-slate-50 text-center transition hover:border-emerald-500 hover:bg-emerald-50">
                                    <img id="imagePreview" src="{{ $editing && $product->image_path ? route('public.media',['subdomain'=>$venue->subdomain,'category'=>'products','filename'=>basename($product->image_path)]) : '' }}" alt="Prévia da imagem" class="{{ $editing && $product->image_path ? '' : 'hidden' }} h-64 w-full object-cover">
                                    <span id="uploadPrompt" class="{{ $editing && $product->image_path ? 'hidden' : '' }} px-5 py-10"><span class="text-4xl text-emerald-600">+</span><strong class="mt-2 block text-slate-800">Clique ou arraste a foto</strong><small class="mt-1 block text-slate-500">JPG, PNG, GIF ou WebP · até 12 MB</small></span>
                                </label>
                                <input id="imageInput" name="image" type="file" accept="image/jpeg,image/png,image/webp,image/gif" class="sr-only">
                                <p id="imageMessage" class="mt-3 text-xs text-slate-500">Redimensionamos para até 1600 px e convertemos para WebP com qualidade 75%.</p>
                            </section>

                            <section class="rounded-2xl border border-emerald-200 bg-emerald-50 p-5 text-sm text-emerald-950">
                                <strong>Dica para vender mais</strong>
                                <p class="mt-1">Use uma foto nítida, um nome direto e descreva os principais ingredientes.</p>
                            </section>
                        </aside>
                    </div>

                    <div class="sticky bottom-4 z-10 flex items-center justify-between gap-3 rounded-2xl bg-slate-900 p-4 shadow-xl">
                        <a href="{{ route('owner.catalog') }}" class="px-3 py-2 text-sm font-bold text-white">Cancelar</a>
                        <button class="rounded-xl bg-emerald-500 px-6 py-3 font-bold text-white hover:bg-emerald-400">{{ $editing ? 'Salvar alterações' : 'Cadastrar produto' }}</button>
                    </div>
                </form>
            @endif

            @if($editing)
                <section class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200 sm:p-6">
                    <div class="flex flex-col gap-2 border-b border-slate-100 pb-5 sm:flex-row sm:items-end sm:justify-between">
                        <div><h3 class="text-lg font-bold text-slate-900">Variações e adicionais</h3><p class="text-sm text-slate-500">Crie grupos como tamanho, ponto da carne, borda ou adicionais.</p></div>
                        <span class="text-xs font-semibold text-slate-500">Mínimo 0 = escolha opcional</span>
                    </div>

                    <form method="post" action="{{ route('owner.option-groups.store', $product) }}" class="mt-5 grid gap-3 rounded-2xl bg-slate-50 p-4 sm:grid-cols-[minmax(0,1fr)_120px_120px_auto]">
                        @csrf
                        <label class="text-sm font-semibold">Novo grupo<input name="name" required maxlength="80" placeholder="Ex.: Escolha o tamanho" class="mt-1 w-full rounded-xl border-slate-300"></label>
                        <label class="text-sm font-semibold">Mínimo<input name="minimum" required type="number" min="0" max="30" value="0" class="mt-1 w-full rounded-xl border-slate-300"></label>
                        <label class="text-sm font-semibold">Máximo<input name="maximum" required type="number" min="1" max="30" value="1" class="mt-1 w-full rounded-xl border-slate-300"></label>
                        <button class="self-end rounded-xl bg-slate-900 px-5 py-2.5 font-bold text-white">Criar grupo</button>
                    </form>

                    <div class="mt-6 space-y-5">
                        @forelse($product->optionGroups as $group)
                            <article class="rounded-2xl border border-slate-200 p-4 sm:p-5">
                                <form method="post" action="{{ route('owner.option-groups.update', $group) }}" class="grid gap-3 sm:grid-cols-[minmax(0,1fr)_100px_100px_120px_auto]">
                                    @csrf @method('PATCH')
                                    <label class="text-xs font-bold text-slate-500">NOME<input name="name" required value="{{ $group->name }}" class="mt-1 w-full rounded-xl border-slate-300 font-semibold text-slate-900"></label>
                                    <label class="text-xs font-bold text-slate-500">MÍNIMO<input name="minimum" required type="number" min="0" max="30" value="{{ $group->minimum }}" class="mt-1 w-full rounded-xl border-slate-300 text-slate-900"></label>
                                    <label class="text-xs font-bold text-slate-500">MÁXIMO<input name="maximum" required type="number" min="1" max="30" value="{{ $group->maximum }}" class="mt-1 w-full rounded-xl border-slate-300 text-slate-900"></label>
                                    <label class="text-xs font-bold text-slate-500">STATUS<select name="active" class="mt-1 w-full rounded-xl border-slate-300 text-slate-900"><option value="1" @selected($group->active)>Ativo</option><option value="0" @selected(!$group->active)>Pausado</option></select></label>
                                    <button class="self-end rounded-xl border border-slate-300 px-4 py-2.5 font-bold">Salvar</button>
                                </form>

                                <div class="mt-4 space-y-3">
                                    @foreach($group->options as $option)
                                        <div class="grid gap-2 rounded-xl bg-slate-50 p-3 sm:grid-cols-[minmax(0,1fr)_minmax(0,1.4fr)_130px_110px_auto]">
                                            <form id="option-{{ $option->id }}" method="post" action="{{ route('owner.options.update', $option) }}" class="contents">@csrf @method('PATCH')
                                                <input name="name" required maxlength="100" value="{{ $option->name }}" aria-label="Nome da opção" class="rounded-lg border-slate-300">
                                                <input name="description" maxlength="300" value="{{ $option->description }}" placeholder="Descrição opcional" aria-label="Descrição" class="rounded-lg border-slate-300">
                                                <input name="price" required type="number" min="0" step="0.01" value="{{ $option->price }}" aria-label="Valor adicional" class="rounded-lg border-slate-300">
                                                <select name="active" aria-label="Status" class="rounded-lg border-slate-300"><option value="1" @selected($option->active)>Ativa</option><option value="0" @selected(!$option->active)>Pausada</option></select>
                                                <button class="rounded-lg border border-slate-300 px-3 py-2 font-bold">Salvar</button>
                                            </form>
                                        </div>
                                    @endforeach
                                </div>

                                <form method="post" action="{{ route('owner.options.store', $group) }}" class="mt-4 grid gap-2 rounded-xl border border-dashed border-emerald-300 bg-emerald-50 p-3 sm:grid-cols-[minmax(0,1fr)_minmax(0,1.4fr)_130px_auto]">
                                    @csrf
                                    <input name="name" required maxlength="100" placeholder="Nome da opção" class="rounded-lg border-slate-300">
                                    <input name="description" maxlength="300" placeholder="Descrição opcional" class="rounded-lg border-slate-300">
                                    <input name="price" required type="number" min="0" step="0.01" value="0" aria-label="Valor adicional" class="rounded-lg border-slate-300">
                                    <button class="rounded-lg bg-emerald-600 px-4 py-2 font-bold text-white">+ Adicionar</button>
                                </form>
                                <form method="post" action="{{ route('owner.option-groups.destroy', $group) }}" class="mt-3 text-right" onsubmit="return confirm('Remover este grupo e todas as opções?')">@csrf @method('DELETE')<button class="text-xs font-bold text-red-600">Excluir grupo</button></form>
                            </article>
                        @empty
                            <p class="rounded-xl bg-slate-50 p-6 text-center text-sm text-slate-500">Este produto ainda não possui variações.</p>
                        @endforelse
                    </div>
                </section>

                <form method="post" action="{{ route('owner.products.destroy', $product) }}" onsubmit="return confirm('Excluir definitivamente este produto?')" class="text-right">@csrf @method('DELETE')<button class="rounded-xl border border-red-200 px-4 py-2 text-sm font-bold text-red-700">Excluir produto</button></form>
            @endif
        </div>
    </div>

    <script>
        const input = document.getElementById('imageInput');
        const zone = document.getElementById('dropZone');
        const preview = document.getElementById('imagePreview');
        const prompt = document.getElementById('uploadPrompt');
        const message = document.getElementById('imageMessage');
        const maxBytes = 12 * 1024 * 1024;

        function showImage(file) {
            if (!file) return;
            if (file.size > maxBytes) {
                input.value = '';
                message.textContent = 'A imagem excede o limite de 12 MB. Escolha outro arquivo.';
                message.className = 'mt-3 text-xs font-bold text-red-600';
                return;
            }
            preview.src = URL.createObjectURL(file);
            preview.classList.remove('hidden');
            prompt.classList.add('hidden');
            message.textContent = file.name + ' selecionada. Será convertida para WebP ao salvar.';
            message.className = 'mt-3 text-xs font-semibold text-emerald-700';
        }

        if (input) input.addEventListener('change', event => showImage(event.target.files[0]));
        if (zone) {
            ['dragenter', 'dragover'].forEach(name => zone.addEventListener(name, event => { event.preventDefault(); zone.classList.add('border-emerald-500', 'bg-emerald-50'); }));
            ['dragleave', 'drop'].forEach(name => zone.addEventListener(name, event => { event.preventDefault(); zone.classList.remove('border-emerald-500', 'bg-emerald-50'); }));
            zone.addEventListener('drop', event => {
                const file = event.dataTransfer.files[0];
                if (!file) return;
                const transfer = new DataTransfer();
                transfer.items.add(file);
                input.files = transfer.files;
                showImage(file);
            });
        }
    </script>
</x-app-layout>
