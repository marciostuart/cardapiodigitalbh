<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#EA4335">
    <title>{{ $title }} | Cardápio Digital BH</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/cardapio-digital-bh-brand.svg') }}">
    <style>
        :root{color-scheme:light;--brand:#ea4335;--yellow:#fbbc05;--ink:#18202c;--muted:#657184;--paper:#fffaf5;--line:#eee6de}
        *{box-sizing:border-box}body{min-height:100vh;margin:0;display:grid;place-items:center;padding:24px;background:radial-gradient(ellipse at top right,#fff0cf 0,transparent 42%),var(--paper);color:var(--ink);font:16px/1.6 Inter,ui-sans-serif,system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif}
        .error-card{width:min(100%,600px);padding:clamp(28px,7vw,56px);border:1px solid rgba(238,230,222,.9);border-radius:28px;background:#fff;box-shadow:0 24px 70px rgba(83,37,26,.12);text-align:center}
        .brand{display:inline-flex;align-items:center;justify-content:center;margin-bottom:24px}.brand img{display:block;width:clamp(120px,34vw,168px);height:auto;object-fit:contain}
        .code{display:inline-flex;align-items:center;justify-content:center;min-width:90px;margin:0 auto 16px;padding:5px 18px;border-radius:999px;background:#fff0e9;color:var(--brand);font-size:18px;font-weight:900;letter-spacing:.08em}
        h1{margin:0;font-size:clamp(25px,6vw,34px);line-height:1.2;letter-spacing:-.04em}p{margin:14px 0 0;color:var(--muted)}.actions{display:flex;flex-wrap:wrap;justify-content:center;gap:10px;margin-top:28px}
        .button{display:inline-flex;min-height:46px;align-items:center;justify-content:center;padding:11px 18px;border:1px solid var(--line);border-radius:12px;background:#fff;color:var(--ink);font-weight:800;text-decoration:none;transition:transform .15s ease,box-shadow .15s ease}.button:hover{transform:translateY(-1px);box-shadow:0 8px 20px rgba(83,37,26,.1)}.button--primary{border-color:var(--brand);background:var(--brand);color:#fff}.button:focus-visible{outline:3px solid var(--yellow);outline-offset:3px}
        .help{display:block;margin-top:28px;color:var(--muted);font-size:12px}
        @media(max-width:440px){body{padding:14px}.error-card{padding:32px 20px;border-radius:22px}.actions{flex-direction:column}.button{width:100%}}
    </style>
</head>
<body>
    <main class="error-card">
        <a class="brand" href="{{ config('app.url') }}" aria-label="Cardápio Digital BH, página inicial"><img src="{{ asset('images/cardapio-digital-bh-brand.svg') }}" alt="Cardápio Digital BH" width="640" height="403"></a>
        <div class="code">{{ $code }}</div>
        <h1>{{ $title }}</h1>
        <p>{{ $message }}</p>
        <div class="actions">
            @if(request()->is('verify-email/*') && auth()->check() && !auth()->user()->hasVerifiedEmail())
                <a class="button button--primary" href="{{ route('verification.notice') }}">Solicitar novo link</a>
            @endif
            @auth
                <a class="button button--primary" href="{{ route('dashboard') }}">Ir para meu painel</a>
            @else
                <a class="button button--primary" href="{{ route('login') }}">Entrar no painel</a>
            @endauth
            <a class="button" href="{{ config('app.url') }}">Voltar ao início</a>
        </div>
        <small class="help">Se precisar, fale com a equipe do Cardápio Digital BH.</small>
    </main>
</body>
</html>
