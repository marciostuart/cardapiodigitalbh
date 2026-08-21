@php
    $customerFirstName = collect(explode(' ', trim((string) session('public_customer_name'))))->filter()->first();
    $menuUrl = route('public.menu', ['subdomain' => $venue->subdomain]);
@endphp
<div class="store-topbar"><div class="store-topbar-inner"><span>Olá{{ $customerFirstName ? ' '.$customerFirstName : '' }}</span><span>{{ ($isOpen ?? true) ? 'Estabelecimento aberto' : 'Estabelecimento fechado' }}</span></div></div>
<header class="store-header">
    <div class="store-header-main">
        <a class="store-brand" href="{{ $menuUrl }}" aria-label="Início do cardápio">
            @if($venue->logo_path)<img class="store-logo" src="{{ route('public.media',['subdomain'=>$venue->subdomain,'category'=>'logos','filename'=>basename($venue->logo_path)]) }}" alt="Logo {{ $venue->name }}">@else<span class="store-logo" aria-hidden="true"></span>@endif
            <span class="store-brand-text">{{ $venue->name }}</span>
        </a>
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
