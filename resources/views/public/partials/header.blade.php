@php
    $consumer = auth('consumer')->user();
    $hasPublicIdentity = $consumer || session()->has('public_customer_name');
    $customerFirstName = collect(explode(' ', trim((string) ($consumer?->name ?: session('public_customer_name')))))->filter()->first();
    $menuUrl = route('public.menu', ['subdomain' => $venue->subdomain]);
@endphp
<style>.mobile-store-meta{display:none}.consumer-logout{display:inline-block;margin-left:5px;color:#fff;opacity:.68;text-decoration:none;font-size:11px;font-weight:500}.consumer-logout:hover,.consumer-logout:focus{opacity:1;text-decoration:underline}.consumer-logout-form{display:inline;margin:0}@media(max-width:720px){.store-topbar{display:none}.store-header-main{display:grid;grid-template-columns:minmax(0,1fr) auto;gap:12px}.store-brand{grid-column:1;min-width:0}.mobile-store-meta{grid-column:2;display:flex;flex-direction:column;align-items:flex-end;gap:5px;color:inherit;font-size:12px;font-weight:800;line-height:1.1;text-align:right;white-space:nowrap}.mobile-store-meta span:last-child{font-size:11px}.header-search{grid-column:1/-1;margin-top:0}}</style>
<div class="store-topbar"><div class="store-topbar-inner"><span>Olá{{ $customerFirstName ? ' '.$customerFirstName : '' }}@if($hasPublicIdentity)<form class="consumer-logout-form" method="post" action="{{ route('public.consumer.logout', ['subdomain' => $venue->subdomain]) }}">@csrf<button class="consumer-logout" type="submit">(sair)</button></form>@endif</span><span>{{ ($isOpen ?? true) ? 'Estamos abertos' : 'Estamos fechados' }}</span></div></div>
<header class="store-header">
    <div class="store-header-main">
        <a class="store-brand" href="{{ $menuUrl }}" aria-label="Início do cardápio">
            @if($venue->logo_path)<img class="store-logo" src="{{ route('public.media',['subdomain'=>$venue->subdomain,'category'=>'logos','filename'=>basename($venue->logo_path)]) }}" alt="Logo {{ $venue->name }}">@else<span class="store-logo" aria-hidden="true"></span>@endif
            <span class="store-brand-text">{{ $venue->name }}</span>
        </a>
        <div class="mobile-store-meta" aria-label="Status do estabelecimento"><span>Olá{{ $customerFirstName ? ' '.$customerFirstName : '' }}@if($hasPublicIdentity)<form class="consumer-logout-form" method="post" action="{{ route('public.consumer.logout', ['subdomain' => $venue->subdomain]) }}">@csrf<button class="consumer-logout" type="submit">(sair)</button></form>@endif</span><span>{{ ($isOpen ?? true) ? 'Estamos abertos' : 'Estamos fechados' }}</span></div>
        <label class="header-search"><input id="productSearch" type="search" placeholder="Digite sua busca..." aria-label="Buscar produtos" data-menu-url="{{ $menuUrl }}"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="11" cy="11" r="6"></circle><path d="m16 16 4 4"></path></svg></label>
    </div>
</header>
<nav id="selectedCategoryRail" class="category-rail" aria-label="Categorias do cardápio">
    @if($isMenu ?? false)
        <button type="button" class="active" data-category-choice="all">Todas</button>@foreach($categories as $category)<button type="button" data-category-choice="{{ $category->id }}">{{ $category->name }}</button>@endforeach
    @else
        <a class="active" href="{{ $menuUrl }}">Todas</a>@foreach($categories as $category)<a href="{{ $menuUrl }}?categoria={{ $category->id }}#categoria-{{ $category->id }}">{{ $category->name }}</a>@endforeach
    @endif
</nav>
