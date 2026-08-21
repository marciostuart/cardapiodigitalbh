<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    @php($seo = $venue->settings ?? [])
    <title>{{ $product->name }} | {{ $seo['seo_title'] ?? $venue->name }}</title>
    <meta name="description" content="{{ Str::limit($product->description ?: 'Conheça '.$product->name.' no cardápio de '.$venue->name, 155) }}">
    <style>
        :root {
            --brand: {{ $venue->primary_color ?? '#087f5b' }};
            --brand-dark: {{ $venue->secondary_color ?? '#102b3c' }};
            --dark: var(--brand-dark);
            --ink: #12212b;
            --muted: #66747d;
            --line: #e4e9e5;
            --paper: #f5f7f5;
            --white: #fff;
        }

        * { box-sizing: border-box; }
        body {
            min-height: 100vh;
            margin: 0;
            display: flex;
            flex-direction: column;
            background: var(--paper);
            color: var(--ink);
            font: 16px Inter, ui-sans-serif, system-ui, -apple-system, sans-serif;
        }

        .store-topbar {
            background: var(--brand);
            color: #fff;
            font-size: 14px;
            font-weight: 750;
        }

        .store-topbar-inner {
            display: flex;
            align-items: center;
            justify-content: space-between;
            max-width: 1400px;
            width: calc(100% - 32px);
            min-height: 34px;
            margin: auto;
        }

        .store-header {
            background: #fff;
            border-bottom: 1px solid var(--line);
            box-shadow: 0 3px 14px #12212b08;
        }

        .store-header-main {
            max-width: 1400px;
            width: calc(100% - 32px);
            min-height: 100px;
            margin: auto;
            display: grid;
            grid-template-columns: minmax(190px, 260px) minmax(300px, 630px);
            justify-content: space-between;
            align-items: center;
            gap: 28px;
        }

        .store-brand {
            display: flex;
            align-items: center;
            gap: 12px;
            min-width: 0;
            color: var(--ink);
            text-decoration: none;
            font-size: 25px;
            font-weight: 900;
            line-height: .92;
            letter-spacing: -1.1px;
        }

        .store-logo {
            width: 62px;
            height: 62px;
            flex: none;
            object-fit: cover;
            border: 2px solid #f0f2ef;
            border-radius: 50%;
            background: #eef3f0;
        }

        .store-brand-text {
            max-width: 165px;
        }

        .store-brand-text::after {
            content: '.';
            color: var(--brand);
            padding-left: 1px;
        }

        .header-search {
            display: flex;
            align-items: center;
            gap: 10px;
            min-width: 0;
            padding: 0 16px;
            border-radius: 10px;
            background: #f4f5f4;
            color: var(--muted);
        }

        .header-search input {
            width: 100%;
            border: 0;
            outline: 0;
            background: transparent;
            padding: 15px 0;
            font: inherit;
            color: var(--ink);
        }

        .header-search svg { width: 20px; height: 20px; flex: none; }

        .category-rail {
            display: flex;
            gap: 8px;
            max-width: 1400px;
            width: calc(100% - 32px);
            margin: 0 auto;
            padding: 14px 0 3px;
            overflow: auto;
            border-bottom: 1px solid var(--line);
            scrollbar-width: thin;
        }

        .category-rail a {
            flex: none;
            border: 0;
            border-bottom: 3px solid transparent;
            background: transparent;
            padding: 9px 13px;
            color: var(--muted);
            font: inherit;
            font-weight: 750;
            white-space: nowrap;
            text-decoration: none;
        }

        .category-rail a.active {
            border-color: var(--brand);
            color: var(--ink);
            font-weight: 900;
        }

        .wrap {
            width: min(1400px, calc(100% - 32px));
            margin: 28px auto 56px;
        }

        .back {
            display: inline-block;
            margin-bottom: 20px;
            color: var(--brand);
            font-weight: 800;
            text-decoration: none;
        }

        .product {
            display: grid;
            gap: 34px;
            background: #fff;
            border: 1px solid var(--line);
            border-radius: 28px;
            padding: clamp(18px, 4vw, 42px);
            box-shadow: 0 18px 55px #102b3c12;
        }

        .main-image {
            width: 100%;
            aspect-ratio: 1/1;
            object-fit: cover;
            border-radius: 20px;
            background: #edf1ee;
        }

        .thumbs {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 9px;
            margin-top: 10px;
        }

        .thumb {
            width: 100%;
            aspect-ratio: 1;
            object-fit: cover;
            border: 2px solid transparent;
            border-radius: 12px;
            cursor: pointer;
        }

        .thumb.active { border-color: var(--brand); }

        h1 {
            font-size: clamp(28px, 4vw, 46px);
            line-height: 1.08;
            margin: 0;
        }

        .description {
            color: var(--muted);
            line-height: 1.65;
        }

        .price {
            font-size: 30px;
            font-weight: 900;
            color: var(--brand-dark);
        }

        .buy {
            display: block;
            margin-top: 24px;
            border: 0;
            border-radius: 14px;
            background: var(--brand);
            color: #fff;
            padding: 16px 24px;
            text-align: center;
            text-decoration: none;
            font-weight: 900;
        }

        .options {
            margin-top: 28px;
            border-top: 1px solid var(--line);
            padding-top: 20px;
        }

        .options > strong {
            display: block;
            margin-bottom: 16px;
        }

        .option-group {
            padding-top: 18px;
        }

        .option-group + .option-group {
            margin-top: 14px;
            border-top: 1px solid var(--line);
        }

        .option-group h3 {
            margin: 0 0 4px;
        }

        .option-group small {
            display: block;
            color: var(--muted);
        }

        .option-list {
            display: grid;
            gap: 10px;
            margin-top: 14px;
        }

        .option-row {
            position: relative;
            display: grid;
            grid-template-columns: auto 1fr auto;
            gap: 12px;
            align-items: start;
            padding: 14px 14px 14px 12px;
            border: 1px solid var(--line);
            border-radius: 16px;
            background: #fafbfa;
            cursor: pointer;
            transition: border-color .18s, background-color .18s, box-shadow .18s;
        }

        .option-row:hover {
            border-color: #cdd8d0;
            box-shadow: 0 10px 24px #12212b08;
        }

        .option-row input {
            position: absolute;
            opacity: 0;
            pointer-events: none;
        }

        .option-check {
            position: relative;
            display: grid;
            place-items: center;
            width: 24px;
            height: 24px;
            margin-top: 1px;
            border: 2px solid #b7c0bb;
            border-radius: 7px;
            background: #fff;
            transition: border-color .18s, background-color .18s, transform .18s;
        }

        .option-check svg {
            width: 15px;
            height: 15px;
            opacity: 0;
            transform: scale(.7);
            transition: opacity .18s, transform .18s;
            fill: none;
            stroke: #fff;
            stroke-width: 3;
            stroke-linecap: round;
            stroke-linejoin: round;
        }

        .option-row input:checked + .option-check {
            background: var(--brand);
            border-color: var(--brand);
            box-shadow: 0 8px 18px color-mix(in srgb, var(--brand) 28%, transparent);
        }

        .option-row input:checked + .option-check svg {
            opacity: 1;
            transform: scale(1);
        }

        .option-copy {
            min-width: 0;
        }

        .option-copy strong {
            display: block;
            font-size: 15px;
            line-height: 1.25;
        }

        .option-copy small {
            display: block;
            margin-top: 2px;
            color: var(--muted);
            font-size: 13px;
            line-height: 1.35;
        }

        .option-price {
            padding-top: 2px;
            color: var(--ink);
            font-weight: 900;
            white-space: nowrap;
        }

        .related {
            margin-top: 44px;
        }

        .related h2 {
            margin-bottom: 18px;
        }

        .related-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 20px;
        }

        .related-card {
            overflow: hidden;
            border: 1px solid var(--line);
            border-radius: 18px;
            background: #fff;
            box-shadow: 0 8px 24px #12212b0c;
            text-decoration: none;
            color: inherit;
        }

        .related-card img,
        .related-card-placeholder {
            display: block;
            width: 100%;
            aspect-ratio: 4/3;
            object-fit: cover;
            background: #edf1ee;
        }

        .related-card-placeholder {
            display: grid;
            place-items: center;
            font-size: 34px;
        }

        .related-card-content {
            padding: 14px;
        }

        .related-card h3 {
            margin: 0;
            font-size: 17px;
        }

        .related-card p {
            display: -webkit-box;
            margin: 7px 0;
            color: var(--muted);
            font-size: 14px;
            line-height: 1.45;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .related-card strong {
            color: var(--brand-dark);
        }

        @media (min-width: 800px) {
            .product {
                grid-template-columns: minmax(0, 1.05fr) minmax(340px, .95fr);
                align-items: start;
            }
        }

        @media (max-width: 720px) {
            .store-topbar-inner,
            .store-header-main {
                width: calc(100% - 24px);
            }

            .store-topbar-inner {
                min-height: 30px;
                font-size: 12px;
            }

            .store-header-main {
                display: block;
                padding: 14px 0;
            }

            .store-brand {
                font-size: 21px;
            }

            .store-logo {
                width: 54px;
                height: 54px;
            }

            .header-search {
                margin-top: 14px;
            }

            .category-rail {
                width: 100%;
                padding-left: 12px;
                padding-right: 12px;
            }

            .wrap {
                width: calc(100% - 24px);
                margin-top: 18px;
            }

            .related-grid {
                display: flex;
                gap: 14px;
                overflow: auto;
                padding-bottom: 8px;
            }

            .related-card {
                flex: 0 0 78%;
            }

            .option-row {
                grid-template-columns: auto 1fr;
                gap: 10px;
            }

            .option-price {
                grid-column: 2;
                justify-self: start;
                padding-top: 0;
            }

            .option-copy small {
                font-size: 12px;
            }
        }
    </style>
    <style>
        .mobile-store-meta { display: none; }

        @media (max-width: 720px) {
            .store-topbar { display: none; }
            .store-header-main {
                display: grid;
                grid-template-columns: minmax(0, 1fr) auto;
                gap: 12px;
                padding: 14px 0;
            }
            .store-brand {
                grid-column: 1;
                min-width: 0;
            }
            .mobile-store-meta {
                grid-column: 2;
                display: flex;
                flex-direction: column;
                align-items: flex-end;
                gap: 5px;
                color: var(--ink);
                font-size: 12px;
                font-weight: 800;
                line-height: 1.1;
                text-align: right;
                white-space: nowrap;
            }
            .mobile-store-meta span:last-child {
                padding: 5px 8px;
                border: 1px solid var(--line);
                border-radius: 999px;
                background: #f4f5f4;
                font-size: 11px;
            }
            .header-search {
                grid-column: 1 / -1;
                margin-top: 0;
            }
        }
    </style>
<style>
.mobile-menu,.mobile-search-toggle{display:none!important}
@media(max-width:720px){
  .store-header{background:transparent!important;border:0!important;box-shadow:none!important}
  .store-header-main{width:100%!important;min-height:0!important;display:grid!important;grid-template-columns:minmax(0,1fr) auto 34px!important;gap:10px!important;padding:12px 16px!important;background:var(--brand)!important}
  .store-brand{grid-column:1!important;justify-content:flex-start!important;color:#fff!important;font-size:20px!important}
  .store-logo{width:54px!important;height:54px!important;border-color:#fff!important}
  .mobile-menu{display:none!important}.mobile-store-meta{grid-column:2!important;display:flex!important;align-items:flex-end!important;color:#fff!important;font-size:12px!important;text-align:right!important}
  .mobile-search-toggle{display:grid!important;grid-column:3!important;place-items:center!important;width:34px;height:34px;border:0;background:transparent;color:#fff;font-size:28px}
  .header-search{display:none;grid-column:1/-1!important;margin:0!important;background:#fff!important}.header-search.mobile-search-open{display:flex!important}
  .category-rail{width:100%!important;padding:10px 12px 2px!important;background:var(--paper)}
  .wrap{width:calc(100% - 24px)!important;margin:18px auto 40px!important;padding:0!important}
  .product{display:block!important;padding:14px!important;border-radius:20px!important}
  .main-image{aspect-ratio:1/1!important;max-height:none!important;border-radius:16px!important}
  .thumbs{grid-template-columns:repeat(5,1fr)!important;gap:6px!important}
  .product h1{font-size:30px!important;margin-top:24px!important}.buy{width:100%!important}
}
@media(min-width:721px){.product{grid-template-columns:minmax(0,1fr) minmax(0,1fr)!important;align-items:start}.product .related{grid-column:1/-1}}
</style></head>
<body>
@include('public.partials.header', ['isMenu' => false])

<main class="wrap">
    <a class="back" href="{{ route('public.menu', ['subdomain' => $venue->subdomain]) }}">← Voltar ao cardápio</a>

    @php($allImages = collect([$product->image_path])->filter()->merge($product->images->pluck('path')))
    <article class="product">
        <section>
            <img id="mainImage" class="main-image" src="{{ $allImages->isNotEmpty() ? route('public.media', ['subdomain' => $venue->subdomain, 'category' => 'products', 'filename' => basename($allImages->first())]) : '' }}" alt="{{ $product->name }}">

            @if($allImages->count() > 1)
                <div class="thumbs">
                    @foreach($allImages as $path)
                        <img class="thumb @if($loop->first) active @endif" src="{{ route('public.media', ['subdomain' => $venue->subdomain, 'category' => 'products', 'filename' => basename($path)]) }}" alt="{{ $product->name }} - imagem {{ $loop->iteration }}">
                    @endforeach
                </div>
            @endif
        </section>

        <section>
            <p style="color:var(--brand);font-weight:900;text-transform:uppercase;letter-spacing:.1em;font-size:12px">{{ $venue->name }}</p>
            <h1>{{ $product->name }}</h1>

            @if($product->description)
                <p class="description">{{ $product->description }}</p>
            @endif

            <p class="price">R$ {{ number_format((float) $product->price, 2, ',', '.') }}</p>

            @if($product->optionGroups->isNotEmpty())
                <div class="options">
                    <strong>Personalize seu pedido</strong>

                    @foreach($product->optionGroups as $group)
                        <section class="option-group">
                            <h3>{{ $group->name }}</h3>
                            <small>{{ $group->minimum ? 'Escolha no mínimo '.$group->minimum : 'Opcional' }} · máximo {{ $group->maximum }}</small>

                            <div class="option-list">
                                @foreach($group->options as $option)
                                    <label class="option-row">
                                        <input type="checkbox" value="{{ $option->id }}">
                                        <span class="option-check" aria-hidden="true">
                                            <svg viewBox="0 0 16 16" focusable="false" aria-hidden="true">
                                                <path d="M3.4 8.4l2.6 2.6 6-6"></path>
                                            </svg>
                                        </span>
                                        <span class="option-copy">
                                            <strong>{{ $option->name }}</strong>
                                            @if($option->description)
                                                <small>{{ $option->description }}</small>
                                            @endif
                                        </span>
                                        <span class="option-price">{{ $option->price > 0 ? '+ R$ '.number_format((float) $option->price, 2, ',', '.') : 'Grátis' }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </section>
                    @endforeach
                </div>
            @endif

            <a class="buy" href="{{ route('public.menu', ['subdomain' => $venue->subdomain, 'produto' => $product->id]) }}">
                {{ $product->optionGroups->isNotEmpty() ? 'Escolher opções' : 'Comprar' }}
            </a>
        </section>
    </article>

    @if($relatedProducts->isNotEmpty())
        <section class="related">
            <h2>Geralmente comprados juntos</h2>
            <div class="related-grid">
                @foreach($relatedProducts as $related)
                    <a class="related-card" href="{{ route('public.product', ['subdomain' => $venue->subdomain, 'product' => $related]) }}">
                        @php($relatedImage = $related->image_path ?: $related->images->first()?->path)
                        @if($relatedImage)
                            <img src="{{ route('public.media', ['subdomain' => $venue->subdomain, 'category' => 'products', 'filename' => basename($relatedImage)]) }}" alt="{{ $related->name }}" loading="lazy">
                        @else
                            <span class="related-card-placeholder">🍽️</span>
                        @endif
                        <div class="related-card-content">
                            <h3>{{ $related->name }}</h3>
                            <p>{{ $related->description ?: 'Confira este produto no cardápio.' }}</p>
                            <strong>R$ {{ number_format((float) $related->price, 2, ',', '.') }}</strong>
                        </div>
                    </a>
                @endforeach
            </div>
        </section>
    @endif
</main>

@include('public.partials.footer')
<button id="productCartBar" class="product-cart-bar" type="button"><span><strong id="productCartCount">0 itens</strong><small>Revisar pedido</small></span><b id="productCartTotal">R$ 0,00</b></button>
<style>.product-cart-bar{display:none;position:fixed;z-index:30;left:50%;bottom:14px;transform:translateX(-50%);width:min(744px,calc(100% - 28px));border:0;border-radius:18px;background:var(--brand);color:#fff;padding:14px 18px;align-items:center;justify-content:space-between;box-shadow:0 16px 50px #0a3d2d55;cursor:pointer}.product-cart-bar.visible{display:flex}.product-cart-bar span{display:block;text-align:left}.product-cart-bar small{display:block;color:#d9f7ea;font-size:11px}.product-cart-bar b{font-size:18px}</style>

<script>
    document.querySelectorAll('.thumb').forEach(thumb => thumb.addEventListener('click', () => {
        document.getElementById('mainImage').src = thumb.src;
        document.querySelectorAll('.thumb').forEach(item => item.classList.remove('active'));
        thumb.classList.add('active');
    }));

    document.getElementById('productSearch')?.addEventListener('keydown', event => {
        if (event.key !== 'Enter') return;
        const term = event.currentTarget.value.trim();
        location.href = event.currentTarget.dataset.menuUrl + (term ? '?busca=' + encodeURIComponent(term) : '');
    });
    document.querySelector('.buy')?.addEventListener('click', event => {
        event.preventDefault();
        const selected = [...document.querySelectorAll('.option-row input:checked')].map(input => ({id:Number(input.value), name:input.closest('.option-row')?.querySelector('.option-copy strong')?.textContent?.trim() || '', price:Number((input.closest('.option-row')?.querySelector('.option-price')?.textContent || '').replace(/[^0-9,]/g,'').replace(',','.')) || 0}));
        const ids = selected.map(option => option.id).sort((a,b)=>a-b); const key = '{{ $product->id }}-'+ids.join('-');
        const items = JSON.parse(localStorage.getItem('cardapiodigitalbh_cart') || '[]'); const current = items.find(item => item.key === key);
        if (current) current.quantity += 1; else items.push({key,id:{{ $product->id }},name:@json($product->name),price:{{ (float)$product->price }} + selected.reduce((sum,o)=>sum+o.price,0),optionIds:ids,optionNames:selected.map(o=>o.name),quantity:1});
        localStorage.setItem('cardapiodigitalbh_cart', JSON.stringify(items));
        renderProductCart();
    });
    const productCartBar=document.getElementById('productCartBar');
    function renderProductCart(){const items=JSON.parse(localStorage.getItem('cardapiodigitalbh_cart')||'[]');const count=items.reduce((s,i)=>s+Number(i.quantity||0),0);const total=items.reduce((s,i)=>s+Number(i.price||0)*Number(i.quantity||0),0);productCartBar.classList.toggle('visible',count>0);document.getElementById('productCartCount').textContent=count+(count===1?' item':' itens');document.getElementById('productCartTotal').textContent=new Intl.NumberFormat('pt-BR',{style:'currency',currency:'BRL'}).format(total)}
    productCartBar?.addEventListener('click',()=>{location.href='{{ route('public.menu',['subdomain'=>$venue->subdomain]) }}'});window.addEventListener('storage',renderProductCart);renderProductCart();
</script>
</body>
</html>
