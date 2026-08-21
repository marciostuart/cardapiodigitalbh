@php
    $consumer = auth('consumer')->user();
    $hasPublicIdentity = $consumer || session()->has('public_customer_name');
    $customerFirstName = collect(explode(' ', trim((string) ($consumer?->name ?: session('public_customer_name')))))->filter()->first();
    $menuUrl = route('public.menu', ['subdomain' => $venue->subdomain]);
@endphp
<style>.mobile-store-meta{display:none}.consumer-logout{display:inline-block;margin:0 0 0 5px;padding:0;border:0;background:transparent;appearance:none;color:#fff;opacity:.7;text-decoration:none;font:inherit;font-size:11px;font-weight:500;line-height:inherit;cursor:pointer}.consumer-logout:hover,.consumer-logout:focus{opacity:1;text-decoration:underline}.consumer-logout-form{display:inline;margin:0}@media(max-width:720px){.store-topbar{display:none}.store-header-main{display:grid;grid-template-columns:minmax(0,1fr) auto;gap:12px}.store-brand{grid-column:1;min-width:0}.mobile-store-meta{grid-column:2;display:flex;flex-direction:column;align-items:flex-end;gap:5px;color:inherit;font-size:12px;font-weight:800;line-height:1.1;text-align:right;white-space:nowrap}.mobile-store-meta span:last-child{font-size:11px}.header-search{grid-column:1/-1;margin-top:0}}</style>
<div class="store-topbar"><div class="store-topbar-inner"><span>Olá{{ $customerFirstName ? ' '.$customerFirstName : '' }}@if($hasPublicIdentity)<form class="consumer-logout-form" method="post" action="{{ route('public.consumer.logout', ['subdomain' => $venue->subdomain]) }}">@csrf<button class="consumer-logout" type="submit">(sair)</button></form>@endif</span><span>{{ ($isOpen ?? true) ? 'Estamos abertos' : 'Estamos fechados' }}</span></div></div>
<header class="store-header">
    @if(!empty(($venue->settings ?? [])['cover_path']))<div class="mobile-header-cover"><img src="{{ route('public.media',['subdomain'=>$venue->subdomain,'category'=>'covers','filename'=>basename($venue->settings['cover_path'])]) }}" alt="Capa de {{ $venue->name }}" loading="eager"><div class="mobile-header-logo">@if($venue->logo_path)<img src="{{ route('public.media',['subdomain'=>$venue->subdomain,'category'=>'logos','filename'=>basename($venue->logo_path)]) }}" alt="Logo {{ $venue->name }}">@endif</div></div>@endif
    <div class="store-header-main">
        <button type="button" class="mobile-menu" aria-label="Abrir menu">☰</button>
        <a class="store-brand" href="{{ $menuUrl }}" aria-label="Início do cardápio">
            @if($venue->logo_path)<img class="store-logo" src="{{ route('public.media',['subdomain'=>$venue->subdomain,'category'=>'logos','filename'=>basename($venue->logo_path)]) }}" alt="Logo {{ $venue->name }}">@else<span class="store-logo" aria-hidden="true"></span>@endif
            <span class="store-brand-text">{{ $venue->name }}</span>
        </a>
        <div class="mobile-store-meta" aria-label="Status do estabelecimento"><span>Olá{{ $customerFirstName ? ' '.$customerFirstName : '' }}@if($hasPublicIdentity)<form class="consumer-logout-form" method="post" action="{{ route('public.consumer.logout', ['subdomain' => $venue->subdomain]) }}">@csrf<button class="consumer-logout" type="submit">(sair)</button></form>@endif</span><span>{{ ($isOpen ?? true) ? 'Estamos abertos' : 'Estamos fechados' }}</span></div>
        <label class="header-search"><input id="productSearch" type="search" placeholder="Digite sua busca..." aria-label="Buscar produtos" data-menu-url="{{ $menuUrl }}"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="11" cy="11" r="6"></circle><path d="m16 16 4 4"></path></svg></label>
        <button type="button" class="mobile-search-toggle" aria-label="Abrir busca">⌕</button>
    </div>
</header>
<nav id="selectedCategoryRail" class="category-rail" aria-label="Categorias do cardápio">
    @if($isMenu ?? false)
        <button type="button" class="active" data-category-choice="all">Todas</button>@foreach($categories as $category)<button type="button" data-category-choice="{{ $category->id }}">{{ $category->name }}</button>@endforeach
    @else
        <a class="active" href="{{ $menuUrl }}">Todas</a>@foreach($categories as $category)<a href="{{ $menuUrl }}?categoria={{ $category->id }}#categoria-{{ $category->id }}">{{ $category->name }}</a>@endforeach
    @endif
</nav>
<section class="mobile-store-info"><h1>{{ $venue->name }}</h1><p>{{ data_get($venue->settings, 'short_description', data_get($venue->settings, 'description', '')) }}</p></section>
<style>@media(max-width:720px){.mobile-header-cover{display:block!important;height:156px!important}.mobile-header-cover>img{width:100%;height:156px;object-fit:cover}.mobile-header-logo{display:grid;place-items:center;position:absolute;left:50%;bottom:-48px;transform:translateX(-50%);width:88px;height:88px;padding:4px;border-radius:8px;background:#fff;box-shadow:0 2px 10px #0003}.mobile-header-logo img{width:100%;height:100%;border-radius:6px;object-fit:cover}.store-header-main{position:relative;z-index:3;padding-top:58px!important}.mobile-store-info{display:block;padding:8px 18px 18px;background:#fff;text-align:center}.mobile-store-info h1{margin:0;font-size:22px}.mobile-store-info p{margin:5px 0 0;color:var(--muted);line-height:1.25}.mobile-scrolled .mobile-header-cover{display:none!important}.mobile-scrolled .store-header-main{position:sticky;top:0;padding-top:10px!important}.mobile-scrolled .store-logo{width:44px!important;height:44px!important}.mobile-scrolled .mobile-store-info{display:none}}.mobile-store-info{display:none}</style>
<script>addEventListener('scroll',()=>document.body.classList.toggle('mobile-scrolled',scrollY>90),{passive:true})</script>
<style>@media(max-width:720px){.mobile-store-info{display:block!important}.mobile-scrolled .mobile-store-info{display:none!important}}</style>
<style>.store-logo{border-radius:6px!important}</style>
<style>.store-header-main,.store-header-nav,.category-rail{max-width:1520px!important}</style>
<style>.mobile-header-cover{display:none}.mobile-search-toggle{display:none}@media(max-width:720px){.mobile-header-cover{display:none}.store-header-main{grid-template-columns:minmax(0,1fr) auto!important;padding:12px 16px!important;background:var(--brand);color:#fff}.mobile-menu{display:none!important}.store-brand{grid-column:1;justify-content:flex-start}.mobile-store-meta{grid-column:2}.mobile-search-toggle{display:grid;grid-column:3;place-items:center;width:34px;height:34px;border:0;background:transparent;color:#fff;font-size:28px;line-height:1;cursor:pointer}.header-search{display:none;grid-column:1/-1}.header-search.mobile-search-open{display:flex}.store-header:has(.mobile-search-open) .mobile-search-toggle{color:#fff}}
</style>
<script>document.addEventListener('DOMContentLoaded',()=>{const b=document.querySelector('.mobile-search-toggle'),s=document.querySelector('.header-search'),i=document.getElementById('productSearch');if(!b||!s)return;b.addEventListener('click',e=>{e.stopPropagation();s.classList.toggle('mobile-search-open');if(s.classList.contains('mobile-search-open'))i.focus()});document.addEventListener('click',e=>{if(!s.contains(e.target)&&e.target!==b)s.classList.remove('mobile-search-open')})})</script>
