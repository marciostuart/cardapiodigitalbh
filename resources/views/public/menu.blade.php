<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $venue->name }} | Cardápio</title>
    <style>
        :root{--ink:#12212b;--muted:#66747d;--brand:#087f5b;--brand-dark:#076349;--paper:#f5f7f5;--white:#fff;--line:#e4e9e5}
        *{box-sizing:border-box}body{margin:0;background:var(--paper);color:var(--ink);font:16px Inter,ui-sans-serif,system-ui,-apple-system,sans-serif}.wrap{max-width:780px;margin:auto;padding:18px 18px 120px}.hero{overflow:hidden;position:relative;background:linear-gradient(135deg,#102b3c,#087f5b);color:white;padding:30px 26px;border-radius:26px;box-shadow:0 16px 45px #102b3c25}.eyebrow{margin:0 0 6px;color:#8ff0cc;font-size:12px;font-weight:800;letter-spacing:.12em;text-transform:uppercase}.hero h1{font-size:clamp(28px,8vw,42px);line-height:1.05;margin:0}.hero .subtitle{max-width:520px;margin:12px 0 0;color:#d9f5ea}.table-badge{display:inline-flex;margin-top:18px;padding:8px 12px;border-radius:999px;background:#ffffff18;border:1px solid #ffffff25;font-size:13px;font-weight:700}.category{margin-top:30px}.category h2{font-size:21px;margin:0 0 12px}.item{background:var(--white);border:1px solid var(--line);border-radius:18px;padding:17px;margin:10px 0;display:grid;grid-template-columns:1fr auto;gap:15px;box-shadow:0 5px 18px #12212b0a}.item h3{font-size:16px;margin:0}.item p{margin:6px 0 0;color:var(--muted);font-size:14px;line-height:1.45}.price{display:block;margin-top:12px;font-weight:850;color:var(--brand-dark)}.add{align-self:center;width:38px;height:38px;border:0;border-radius:12px;background:#e6f6f0;color:var(--brand);font-size:24px;line-height:1;cursor:pointer}.empty{padding:28px;background:white;border:1px dashed #cad4cc;border-radius:18px;color:var(--muted);text-align:center}.cart{position:fixed;z-index:10;left:50%;bottom:14px;transform:translateX(-50%);width:min(744px,calc(100% - 28px));border:0;border-radius:18px;background:var(--brand);color:white;padding:15px 18px;display:none;align-items:center;justify-content:space-between;gap:14px;box-shadow:0 16px 50px #0a3d2d55;cursor:pointer}.cart.visible{display:flex}.cart strong{display:block}.cart small{color:#c9f3e3}.cart-total{font-size:18px;font-weight:900}.panel{position:fixed;z-index:20;inset:0;background:#10212bcc;display:none;align-items:flex-end;justify-content:center}.panel.open{display:flex}.sheet{width:min(780px,100%);max-height:86vh;overflow:auto;background:white;border-radius:26px 26px 0 0;padding:24px 20px calc(24px + env(safe-area-inset-bottom))}.sheet-head{display:flex;justify-content:space-between;align-items:center}.sheet h2{margin:0}.close{border:0;background:#edf1ee;border-radius:50%;width:36px;height:36px;font-size:20px}.cart-line{display:grid;grid-template-columns:1fr auto;gap:12px;padding:15px 0;border-bottom:1px solid var(--line)}.qty{display:flex;align-items:center;gap:10px}.qty button{border:1px solid var(--line);background:white;width:30px;height:30px;border-radius:9px}.checkout{width:100%;margin-top:20px;border:0;border-radius:14px;background:var(--brand);color:white;padding:15px;font-size:16px;font-weight:800}.checkout:disabled{opacity:.55}.notice{display:none;margin-top:14px;padding:12px;border-radius:12px;background:#e8f7f1;color:#075b43}.notice.error{background:#fff0f0;color:#9f2727}@media(min-width:600px){.wrap{padding-top:28px}.item{padding:20px}}
    </style>
</head>
<body>
<main class="wrap">
    <header class="hero">
        <p class="eyebrow">Cardápio digital</p>
        <h1>{{ $venue->name }}</h1>
        <p class="subtitle">Escolha com calma. Preços e disponibilidade atualizados pelo estabelecimento.</p>
        @isset($table)<span class="table-badge">Mesa {{ $table->name ?: $table->code }}</span>@endisset
    </header>

    @forelse($categories as $category)
        <section class="category">
            <h2>{{ $category->name }}</h2>
            @forelse($category->products as $product)
                <article class="item">
                    <div>
                        <h3>{{ $product->name }}</h3>
                        @if($product->description)<p>{{ $product->description }}</p>@endif
                        <span class="price">R$ {{ number_format((float) $product->price, 2, ',', '.') }}</span>
                    </div>
                    @isset($table)
                        <button class="add" type="button" aria-label="Adicionar {{ $product->name }}" data-product='@json(["id" => $product->id, "name" => $product->name, "price" => (float) $product->price])'>+</button>
                    @endisset
                </article>
            @empty
                <p class="empty">Nenhum item disponível nesta categoria.</p>
            @endforelse
        </section>
    @empty
        <p class="empty">Este cardápio ainda está sendo preparado.</p>
    @endforelse
</main>

@isset($table)
    <button id="cartButton" class="cart" type="button"><span><strong id="cartCount">0 itens</strong><small>Revisar pedido</small></span><span id="cartTotal" class="cart-total">R$ 0,00</span></button>
    <div id="cartPanel" class="panel" role="dialog" aria-modal="true" aria-label="Seu pedido">
        <div class="sheet">
            <div class="sheet-head"><h2>Seu pedido</h2><button id="closeCart" class="close" type="button" aria-label="Fechar">×</button></div>
            <div id="cartLines"></div>
            <div id="notice" class="notice"></div>
            <button id="checkout" class="checkout" type="button">Enviar pedido para a cozinha</button>
        </div>
    </div>
    <script>
        (() => {
            const cart = new Map();
            const money = value => new Intl.NumberFormat('pt-BR', {style:'currency', currency:'BRL'}).format(value);
            const button = document.getElementById('cartButton');
            const panel = document.getElementById('cartPanel');
            const lines = document.getElementById('cartLines');
            const notice = document.getElementById('notice');
            const checkout = document.getElementById('checkout');
            const escapeHtml = value => { const div = document.createElement('div'); div.textContent = value; return div.innerHTML; };

            function render() {
                const entries = [...cart.values()];
                const count = entries.reduce((sum, item) => sum + item.quantity, 0);
                const total = entries.reduce((sum, item) => sum + item.price * item.quantity, 0);
                button.classList.toggle('visible', count > 0);
                document.getElementById('cartCount').textContent = count + (count === 1 ? ' item' : ' itens');
                document.getElementById('cartTotal').textContent = money(total);
                lines.innerHTML = entries.map(item => `<div class="cart-line"><div><strong>${escapeHtml(item.name)}</strong><div>${money(item.price * item.quantity)}</div></div><div class="qty"><button type="button" data-less="${item.id}">−</button><b>${item.quantity}</b><button type="button" data-more="${item.id}">+</button></div></div>`).join('');
            }
            document.querySelectorAll('[data-product]').forEach(el => el.addEventListener('click', () => { const item = JSON.parse(el.dataset.product); const current = cart.get(item.id) || {...item, quantity:0}; current.quantity++; cart.set(item.id, current); render(); }));
            lines.addEventListener('click', event => { const id = Number(event.target.dataset.less || event.target.dataset.more); if (!id || !cart.has(id)) return; const item = cart.get(id); item.quantity += event.target.dataset.more ? 1 : -1; item.quantity > 0 ? cart.set(id, item) : cart.delete(id); render(); });
            button.addEventListener('click', () => panel.classList.add('open'));
            document.getElementById('closeCart').addEventListener('click', () => panel.classList.remove('open'));
            panel.addEventListener('click', event => { if (event.target === panel) panel.classList.remove('open'); });
            checkout.addEventListener('click', async () => {
                checkout.disabled = true; notice.style.display = 'none';
                try {
                    const response = await fetch(@json(route('public.qr-order', $table->qr_token)), {method:'POST', headers:{'Content-Type':'application/json','Accept':'application/json','X-CSRF-TOKEN':document.querySelector('meta[name="csrf-token"]').content}, body:JSON.stringify({items:[...cart.values()].map(item => ({product_id:item.id, quantity:item.quantity}))})});
                    if (!response.ok) throw new Error('Não foi possível enviar o pedido. Tente novamente.');
                    cart.clear(); render(); notice.className = 'notice'; notice.textContent = 'Pedido enviado com sucesso! A cozinha já recebeu.'; notice.style.display = 'block'; checkout.textContent = 'Pedido enviado';
                } catch (error) { notice.className = 'notice error'; notice.textContent = error.message; notice.style.display = 'block'; checkout.disabled = false; }
            });
            render();
        })();
    </script>
@endisset
</body>
</html>
