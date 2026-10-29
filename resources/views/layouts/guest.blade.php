<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#EA4335">
    <title>{{ $title ?? 'Acesso | Cardápio Digital BH' }}</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/cardapio-digital-bh-brand.svg') }}">
    <link rel="icon" type="image/webp" href="{{ asset('images/cardapio-digital-bh-favicon.webp') }}">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet"/>
    @vite(['resources/css/app.css','resources/js/app.js'])
    <style>
        body{background:#fffaf6}
        .auth-shell{min-height:100vh;display:grid;grid-template-columns:minmax(0,1fr) minmax(0,45%)}
        .auth-side{background:radial-gradient(circle at 78% 18%,rgba(251,188,5,.3),transparent 30%),linear-gradient(145deg,#b9362b,#681f1b);padding:clamp(35px,6vw,78px);color:#fff;display:flex;flex-direction:column;justify-content:space-between}
        .brand{display:flex;align-items:center;gap:12px;color:#fff;font-weight:800;font-size:20px;letter-spacing:-.6px;text-decoration:none}
        .brand img{display:block;width:min(170px,55vw);height:auto;max-height:108px;object-fit:contain;background:#fff;border-radius:16px;padding:12px}
        .auth-side h1{font-size:clamp(36px,5vw,56px);letter-spacing:-2px;line-height:1.02;max-width:600px;margin:30px 0 20px}
        .auth-side p{max-width:470px;color:#f1dfd5;font-size:17px}
        .auth-side .eyebrow{color:#fbbc05}
        .benefits{display:grid;gap:13px;color:#f1dfd5;font-size:14px}
        .benefits b{display:inline-grid;place-items:center;background:#fbbc05;color:#39261c;border-radius:50%;width:21px;height:21px;margin-right:8px}
        .auth-main{display:flex;align-items:center;justify-content:center;padding:28px}
        .auth-card{width:min(100%,470px)}
        .back{font-size:14px;color:#695e59}
        .auth-card h2{font-size:31px;letter-spacing:-1.1px;margin:28px 0 6px;color:#302724}
        .subtitle{color:#766b66;margin:0 0 26px}
        .submit{width:100%;justify-content:center;background:#ea4335!important;padding:13px!important;border-radius:10px!important;font-size:15px}
        .submit:hover{background:#d9362a!important}
        .helper{font-size:13px;color:#766b66;text-align:center;margin-top:20px}
        .helper a{color:#c9362a;font-weight:700}
        @media(max-width:760px){.auth-shell{grid-template-columns:1fr}.auth-side{gap:30px;padding:28px 24px}.auth-side h1{font-size:36px;margin:20px 0 12px}.auth-side p{font-size:15px}.benefits{grid-template-columns:1fr;gap:8px}.auth-main{padding:28px 20px 40px}}
    </style>
</head>
<body>
    <div class="auth-shell">
        <aside class="auth-side">
            <a class="brand" href="/" aria-label="Cardápio Digital BH, página inicial"><img src="{{ asset('images/cardapio-digital-bh-brand.svg') }}" alt="Cardápio Digital BH" width="640" height="403" fetchpriority="high"></a>
            <div>
                <p class="eyebrow uppercase text-xs font-bold tracking-widest">Operação para alimentação</p>
                <h1>Seu salão no ritmo certo.</h1>
                <p>Cardápio digital, pedidos, comandas, caixa e cozinha em uma plataforma feita para o dia a dia do seu estabelecimento.</p>
            </div>
            <div class="benefits">
                <span><b>✓</b>Link e QR Code exclusivos</span>
                <span><b>✓</b>Sem aplicativo para o cliente</span>
                <span><b>✓</b>Comece com sua conta gratuita</span>
            </div>
        </aside>
        <main class="auth-main">
            <section class="auth-card"><a href="/" class="back">← Voltar para o site</a>{{ $slot }}</section>
        </main>
    </div>
    @include('layouts.cookie-consent')
</body>
</html>
