<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $venue->name }} | Cardápio</title>
    <style>
        :root{--ink:#12212b;--muted:#66747d;--brand:{{ $venue->primary_color ?? '#087f5b' }};--brand-dark:{{ $venue->secondary_color ?? '#102b3c' }};--paper:#f5f7f5;--white:#fff;--line:#e4e9e5}
        *{box-sizing:border-box}body{margin:0;background:var(--paper);color:var(--ink);font:16px Inter,ui-sans-serif,system-ui,-apple-system,sans-serif}.wrap{max-width:780px;margin:auto;padding:18px 18px 120px}.hero{overflow:hidden;position:relative;background:linear-gradient(135deg,#102b3c,#087f5b);color:white;padding:30px 26px;border-radius:26px;box-shadow:0 16px 45px #102b3c25}.eyebrow{margin:0 0 6px;color:#8ff0cc;font-size:12px;font-weight:800;letter-spacing:.12em;text-transform:uppercase}.hero h1{font-size:clamp(28px,8vw,42px);line-height:1.05;margin:0}.hero .subtitle{max-width:520px;margin:12px 0 0;color:#d9f5ea}.table-badge{display:inline-flex;margin-top:18px;padding:8px 12px;border-radius:999px;background:#ffffff18;border:1px solid #ffffff25;font-size:13px;font-weight:700}.category{margin-top:30px}.category h2{font-size:21px;margin:0 0 12px}.item{background:var(--white);border:1px solid var(--line);border-radius:18px;padding:17px;margin:10px 0;display:grid;grid-template-columns:1fr auto;gap:15px;box-shadow:0 5px 18px #12212b0a}.item h3{font-size:16px;margin:0}.item p{margin:6px 0 0;color:var(--muted);font-size:14px;line-height:1.45}.price{display:block;margin-top:12px;font-weight:850;color:var(--brand-dark)}.add{align-self:center;width:38px;height:38px;border:0;border-radius:12px;background:#e6f6f0;color:var(--brand);font-size:24px;line-height:1;cursor:pointer}.empty{padding:28px;background:white;border:1px dashed #cad4cc;border-radius:18px;color:var(--muted);text-align:center}.cart{position:fixed;z-index:10;left:50%;bottom:14px;transform:translateX(-50%);width:min(744px,calc(100% - 28px));border:0;border-radius:18px;background:var(--brand);color:white;padding:15px 18px;display:none;align-items:center;justify-content:space-between;gap:14px;box-shadow:0 16px 50px #0a3d2d55;cursor:pointer}.cart.visible{display:flex}.cart strong{display:block}.cart small{color:#c9f3e3}.cart-total{font-size:18px;font-weight:900}.panel{position:fixed;z-index:20;inset:0;background:#10212bcc;display:none;align-items:flex-end;justify-content:center}.panel.open{display:flex}.sheet{width:min(780px,100%);max-height:86vh;overflow:auto;background:white;border-radius:26px 26px 0 0;padding:24px 20px calc(24px + env(safe-area-inset-bottom))}.sheet-head{display:flex;justify-content:space-between;align-items:center}.sheet h2{margin:0}.close{border:0;background:#edf1ee;border-radius:50%;width:36px;height:36px;font-size:20px}.cart-line{display:grid;grid-template-columns:1fr auto;gap:12px;padding:15px 0;border-bottom:1px solid var(--line)}.qty{display:flex;align-items:center;gap:10px}.qty button{border:1px solid var(--line);background:white;width:30px;height:30px;border-radius:9px}.checkout{width:100%;margin-top:20px;border:0;border-radius:14px;background:var(--brand);color:white;padding:15px;font-size:16px;font-weight:800}.checkout:disabled{opacity:.55}.notice{display:none;margin-top:14px;padding:12px;border-radius:12px;background:#e8f7f1;color:#075b43}.notice.error{background:#fff0f0;color:#9f2727}@media(min-width:600px){.wrap{padding-top:28px}.item{padding:20px}}
    </style>
</head>
<body>
<main class="wrap">
    <header class="hero">
        <p class="eyebrow">Cardápio digital</p>
        @if($venue->logo_path)<img src="{{ route('public.media',['subdomain'=>$venue->subdomain,'category'=>'logos','filename'=>basename($venue->logo_path)]) }}" alt="Logo {{ $venue->name }}" style="max-width:150px;max-height:90px;object-fit:contain;margin-bottom:14px">@endif
        <h1>{{ $venue->name }}</h1>
        <p class="subtitle">Escolha com calma. Preços e disponibilidade atualizados pelo estabelecimento.</p>
        <span class="table-badge">{{ ($isOpen ?? true) ? 'Aberto para pedidos' : 'Fechado no momento' }}</span>
        @isset($table)<span class="table-badge">Mesa {{ $table->name ?: $table->code }}</span>@endisset
    </header>
    @if($banners->isNotEmpty())<section style="display:grid;gap:12px;margin-top:18px">@foreach($banners as $banner)<a @if($banner->link_url)href="{{ $banner->link_url }}" rel="noopener"@endif style="display:block;overflow:hidden;border-radius:20px;background:white"><img src="{{ route('public.media',['subdomain'=>$venue->subdomain,'category'=>'banners','filename'=>basename($banner->image_path)]) }}" alt="{{ $banner->title }} - {{ $venue->name }}" style="width:100%;max-height:260px;object-fit:cover"><span style="display:block;padding:12px 16px"><strong>{{ $banner->title }}</strong>@if($banner->description)<small style="display:block;color:var(--muted)">{{ $banner->description }}</small>@endif</span></a>@endforeach</section>@endif

    @forelse($categories as $category)
        <section class="category">
            <h2>{{ $category->name }}</h2>
            @forelse($category->products as $product)
                @php
                    $productPayload = [
                        'id' => $product->id,
                        'name' => $product->name,
                        'price' => (float) $product->price,
                        'groups' => $product->optionGroups->map(function ($group) {
                            return ['id' => $group->id, 'name' => $group->name, 'minimum' => $group->minimum, 'maximum' => $group->maximum, 'options' => $group->options->map(function ($option) {
                                return ['id' => $option->id, 'name' => $option->name, 'description' => $option->description, 'price' => (float) $option->price];
                            })->values()];
                        })->values(),
                    ];
                @endphp
                <article class="item">
                    <div>
                        <h3>{{ $product->name }}</h3>
                        @if($product->image_path)<img src="{{ route('public.media',['subdomain'=>$venue->subdomain,'category'=>'products','filename'=>basename($product->image_path)]) }}" alt="{{ $product->name }} - {{ $venue->name }}" loading="lazy" style="width:100%;max-height:220px;object-fit:cover;border-radius:12px;margin-top:10px">@endif
                        @if($product->description)<p>{{ $product->description }}</p>@endif
                        <span class="price">R$ {{ number_format((float) $product->price, 2, ',', '.') }}</span>
                    </div>
                    @if((isset($table) || $venue->pickup_enabled || $venue->delivery_enabled) && ($isOpen ?? true))
                        <button class="add" type="button" aria-label="Adicionar {{ $product->name }}" data-product='@json($productPayload)'>+</button>
                    @endif
                </article>
            @empty
                <p class="empty">Nenhum item disponível nesta categoria.</p>
            @endforelse
        </section>
    @empty
        <p class="empty">Este cardápio ainda está sendo preparado.</p>
    @endforelse
</main>

@if(isset($table) || $venue->pickup_enabled || $venue->delivery_enabled)
    @php($orderEndpoint = isset($table) ? route('public.tenant-table-order', ['subdomain' => $venue->subdomain, 'token' => $table->qr_token]) : route('public.orders.store', ['subdomain' => $venue->subdomain]))
    <div id="customPanel" class="panel" role="dialog" aria-modal="true" aria-label="Personalizar produto">
        <div class="sheet"><div class="sheet-head"><div><h2 id="customTitle">Personalizar</h2><small style="color:var(--muted)">Monte do seu jeito</small></div><button id="closeCustom" class="close" type="button" aria-label="Fechar">×</button></div><div id="customGroups"></div><div id="customError" class="notice error"></div><button id="confirmCustom" class="checkout" type="button">Adicionar ao pedido</button></div>
    </div>
    <button id="cartButton" class="cart" type="button"><span><strong id="cartCount">0 itens</strong><small>Revisar pedido</small></span><span id="cartTotal" class="cart-total">R$ 0,00</span></button>
    <div id="cartPanel" class="panel" role="dialog" aria-modal="true" aria-label="Seu pedido">
        <div class="sheet">
            <div class="sheet-head"><h2>Seu pedido</h2><button id="closeCart" class="close" type="button" aria-label="Fechar">×</button></div>
            <div id="cartLines"></div>
            @if(!isset($table))
                <label style="display:block;margin-top:18px;font-weight:700">Como deseja receber?</label>
                <select id="serviceChannel" style="width:100%;margin-top:8px;padding:12px;border:1px solid var(--line);border-radius:12px">@if($venue->pickup_enabled)<option value="pickup">Retirar no estabelecimento</option>@endif @if($venue->delivery_enabled)<option value="delivery">Receber por delivery</option>@endif</select>
                @if($venue->delivery_enabled)<div id="deliveryFields" style="display:none"><select id="deliveryZone" style="width:100%;margin-top:10px;padding:12px;border:1px solid var(--line);border-radius:12px"><option value="">Selecione a região de entrega</option>@foreach($deliveryZones as $zone)<option value="{{ $zone->id }}">{{ $zone->name }} · R$ {{ number_format((float)$zone->fee,2,',','.') }}</option>@endforeach</select><div style="display:grid;grid-template-columns:1fr 1fr;gap:8px"><input id="postalCode" inputmode="numeric" maxlength="9" placeholder="CEP" style="margin-top:10px;padding:12px;border:1px solid var(--line);border-radius:12px"><input id="number" maxlength="30" placeholder="Número" style="margin-top:10px;padding:12px;border:1px solid var(--line);border-radius:12px"></div><input id="street" maxlength="150" placeholder="Rua" style="width:100%;margin-top:8px;padding:12px;border:1px solid var(--line);border-radius:12px"><input id="neighborhood" maxlength="100" placeholder="Bairro" style="width:100%;margin-top:8px;padding:12px;border:1px solid var(--line);border-radius:12px"><div style="display:grid;grid-template-columns:1fr 80px;gap:8px"><input id="city" maxlength="100" placeholder="Município" style="margin-top:8px;padding:12px;border:1px solid var(--line);border-radius:12px"><input id="state" maxlength="2" placeholder="UF" style="margin-top:8px;padding:12px;border:1px solid var(--line);border-radius:12px;text-transform:uppercase"></div><input id="complement" maxlength="100" placeholder="Complemento" style="width:100%;margin-top:8px;padding:12px;border:1px solid var(--line);border-radius:12px"><input id="reference" maxlength="150" placeholder="Referência" style="width:100%;margin-top:8px;padding:12px;border:1px solid var(--line);border-radius:12px"></div>@endif
                <select id="paymentMethod" style="width:100%;margin-top:10px;padding:12px;border:1px solid var(--line);border-radius:12px"><option value="pix">PIX</option><option value="cash">Dinheiro</option><option value="debit">Cartão de débito</option><option value="credit">Cartão de crédito</option></select>
                <input id="changeFor" type="number" min="0" step="0.01" placeholder="Troco para (opcional)" style="width:100%;margin-top:10px;padding:12px;border:1px solid var(--line);border-radius:12px">
                <input id="couponCode" maxlength="40" placeholder="Cupom (opcional)" style="width:100%;margin-top:10px;padding:12px;border:1px solid var(--line);border-radius:12px;text-transform:uppercase">
            @endif
            <label style="display:block;margin-top:18px;font-weight:700">Comanda individual <small style="display:block;color:var(--muted);font-weight:400">Informe seu nome apenas se quiser separar sua conta das demais pessoas da mesa.</small></label>
            <input id="customerName" maxlength="80" placeholder="Seu nome (opcional)" style="width:100%;margin-top:8px;padding:12px;border:1px solid var(--line);border-radius:12px">
            <input id="customerPhone" inputmode="tel" maxlength="20" placeholder="WhatsApp com DDD (opcional)" style="width:100%;margin-top:10px;padding:12px;border:1px solid var(--line);border-radius:12px">
            <label style="display:flex;gap:8px;align-items:flex-start;margin-top:10px;color:var(--muted);font-size:13px"><input id="whatsappConsent" type="checkbox" style="margin-top:3px"> Quero receber pelo WhatsApp as atualizações deste pedido.</label>
            <textarea id="orderNotes" maxlength="500" rows="2" placeholder="Observações do pedido (opcional)" style="width:100%;margin-top:10px;padding:12px;border:1px solid var(--line);border-radius:12px;font:inherit"></textarea>
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
            const customPanel = document.getElementById('customPanel');
            const customGroups = document.getElementById('customGroups');
            const customError = document.getElementById('customError');
            let customizing = null;
            let coordinates = null;
            const escapeHtml = value => { const div = document.createElement('div'); div.textContent = value; return div.innerHTML; };

            function render() {
                const entries = [...cart.values()];
                const count = entries.reduce((sum, item) => sum + item.quantity, 0);
                const total = entries.reduce((sum, item) => sum + item.price * item.quantity, 0);
                button.classList.toggle('visible', count > 0);
                document.getElementById('cartCount').textContent = count + (count === 1 ? ' item' : ' itens');
                document.getElementById('cartTotal').textContent = money(total);
                lines.innerHTML = entries.map(item => `<div class="cart-line"><div><strong>${escapeHtml(item.name)}</strong>${item.optionNames.length ? `<small style="display:block;color:var(--muted)">${escapeHtml(item.optionNames.join(', '))}</small>` : ''}<div>${money(item.price * item.quantity)}</div></div><div class="qty"><button type="button" data-less="${item.key}">−</button><b>${item.quantity}</b><button type="button" data-more="${item.key}">+</button></div></div>`).join('');
            }
            function addItem(product, selected = []) { const optionIds = selected.map(option => option.id).sort((a,b)=>a-b); const key = product.id+'-'+optionIds.join('-'); const price = product.price + selected.reduce((sum, option) => sum + option.price, 0); const current = cart.get(key) || {key,id:product.id,name:product.name,price,optionIds,optionNames:selected.map(option=>option.name),quantity:0}; current.quantity++; cart.set(key,current); render(); }
            function openCustomizer(product) { customizing=product; document.getElementById('customTitle').textContent=product.name; customError.style.display='none'; customGroups.innerHTML=product.groups.map(group=>`<section style="margin-top:18px;padding-top:16px;border-top:1px solid var(--line)" data-group="${group.id}" data-min="${group.minimum}" data-max="${group.maximum}"><h3 style="margin:0 0 4px">${escapeHtml(group.name)}</h3><small style="color:var(--muted)">${group.minimum ? `Escolha no mínimo ${group.minimum}` : 'Opcional'} · máximo ${group.maximum}</small>${group.options.map(option=>`<div style="display:flex;justify-content:space-between;gap:12px;padding:11px 0"><label style="display:flex;gap:9px;align-items:center"><input type="checkbox" value="${option.id}"><span>${escapeHtml(option.name)}${option.description ? `<small style="display:block;color:var(--muted)">${escapeHtml(option.description)}</small>` : ''}</span></label><b>${option.price ? '+ '+money(option.price) : 'Grátis'}</b></div>`).join('')}</section>`).join(''); customPanel.classList.add('open'); }
            document.querySelectorAll('[data-product]').forEach(el => el.addEventListener('click', () => { const product=JSON.parse(el.dataset.product); product.groups.length ? openCustomizer(product) : addItem(product); }));
            document.getElementById('confirmCustom').addEventListener('click',()=>{ const selected=[]; for(const groupElement of customGroups.querySelectorAll('[data-group]')){ const checked=[...groupElement.querySelectorAll('input:checked')]; const min=Number(groupElement.dataset.min),max=Number(groupElement.dataset.max); if(checked.length<min||checked.length>max){ customError.textContent=`Revise as escolhas de ${groupElement.querySelector('h3').textContent}.`;customError.style.display='block';return;} checked.forEach(input=>{const group=customizing.groups.find(group=>group.id===Number(groupElement.dataset.group));selected.push(group.options.find(option=>option.id===Number(input.value)));}); } addItem(customizing,selected); customPanel.classList.remove('open'); });
            document.getElementById('closeCustom').addEventListener('click',()=>customPanel.classList.remove('open'));
            customPanel.addEventListener('click',event=>{if(event.target===customPanel)customPanel.classList.remove('open')});
            lines.addEventListener('click', event => { const key = event.target.dataset.less || event.target.dataset.more; if (!key || !cart.has(key)) return; const item = cart.get(key); item.quantity += event.target.dataset.more ? 1 : -1; item.quantity > 0 ? cart.set(key, item) : cart.delete(key); render(); });
            button.addEventListener('click', () => panel.classList.add('open'));
            document.getElementById('closeCart').addEventListener('click', () => panel.classList.remove('open'));
            panel.addEventListener('click', event => { if (event.target === panel) panel.classList.remove('open'); });
            const channel = document.getElementById('serviceChannel');
            const deliveryFields = document.getElementById('deliveryFields');
            if (channel && deliveryFields) { const toggleDelivery = () => deliveryFields.style.display = channel.value === 'delivery' ? 'block' : 'none'; channel.addEventListener('change', toggleDelivery); toggleDelivery(); }
            const postalCode = document.getElementById('postalCode');
            if (postalCode) postalCode.addEventListener('input', async () => { const cep = postalCode.value.replace(/\D/g,''); if (cep.length !== 8) return; postalCode.value = cep.slice(0,5)+'-'+cep.slice(5); try { const data = await fetch('https://viacep.com.br/ws/'+cep+'/json/').then(r=>r.json()); if(data.erro) return; document.getElementById('street').value=data.logradouro||''; document.getElementById('neighborhood').value=data.bairro||''; document.getElementById('city').value=data.localidade||''; document.getElementById('state').value=data.uf||''; } catch (_) {} });
            checkout.addEventListener('click', async () => {
                checkout.disabled = true; notice.style.display = 'none';
                try {
                    @if($venue->geofence_enabled)
                    if (!coordinates) coordinates = await new Promise((resolve, reject) => navigator.geolocation ? navigator.geolocation.getCurrentPosition(p => resolve({latitude:p.coords.latitude,longitude:p.coords.longitude,location_accuracy:p.coords.accuracy}), () => reject(new Error('Autorize a localização para pedir nesta mesa.')), {enableHighAccuracy:true,timeout:12000,maximumAge:30000}) : reject(new Error('Seu navegador não oferece localização.')));
                    @endif
                    const phone = document.getElementById('customerPhone').value;
                    const consent = document.getElementById('whatsappConsent').checked;
                    if (consent && !phone.trim()) throw new Error('Informe o WhatsApp para receber atualizações.');
                    const payload = {...coordinates, channel:channel?.value, customer_name:document.getElementById('customerName').value, customer_phone:phone, whatsapp_consent:consent, payment_method:document.getElementById('paymentMethod')?.value, change_for:document.getElementById('changeFor')?.value||null, coupon_code:document.getElementById('couponCode')?.value||null, delivery_zone_id:document.getElementById('deliveryZone')?.value||null, postal_code:postalCode?.value, street:document.getElementById('street')?.value, number:document.getElementById('number')?.value, complement:document.getElementById('complement')?.value, neighborhood:document.getElementById('neighborhood')?.value, city:document.getElementById('city')?.value, state:document.getElementById('state')?.value, reference:document.getElementById('reference')?.value, notes:document.getElementById('orderNotes').value, items:[...cart.values()].map(item => ({product_id:item.id, quantity:item.quantity, option_ids:item.optionIds}))};
                    const response = await fetch(@json($orderEndpoint), {method:'POST', headers:{'Content-Type':'application/json','Accept':'application/json','X-CSRF-TOKEN':document.querySelector('meta[name="csrf-token"]').content}, body:JSON.stringify(payload)});
                    const data = await response.json(); if (!response.ok) throw new Error(data.message || Object.values(data.errors||{})[0]?.[0] || 'Não foi possível enviar o pedido. Tente novamente.');
                    cart.clear(); render(); notice.className = 'notice'; notice.textContent = 'Pedido enviado com sucesso! A cozinha já recebeu.'; notice.style.display = 'block'; checkout.textContent = 'Pedido enviado';
                    if (data.tracking_url) setTimeout(() => window.location.href = data.tracking_url, 900);
                } catch (error) { notice.className = 'notice error'; notice.textContent = error.message; notice.style.display = 'block'; checkout.disabled = false; }
            });
            render();
        })();
    </script>
@endif
</body>
</html>
