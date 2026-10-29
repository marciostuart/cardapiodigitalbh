<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="theme-color" content="#EA4335">
    <title>Cardápio Digital em Belo Horizonte com QR Code | Cardápio Digital BH</title>
    <meta name="description" content="Crie um cardápio digital em Belo Horizonte com QR Code, atualização de preços, pedidos por mesa, comandas, cozinha e caixa. Comece grátis.">
    <link rel="canonical" href="https://cardapiodigitalbh.com.br/cardapio-digital-em-belo-horizonte">
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/cardapio-digital-bh-logo.svg') }}">
    <link rel="icon" type="image/webp" href="{{ asset('images/cardapio-digital-bh-favicon.webp') }}">
    <style>
        :root{--ink:#302724;--brand:#ea4335;--accent:#fbbc05;--muted:#6a5f5a;--paper:#fffaf6;--line:#f0e4dc}
        *{box-sizing:border-box}html,body{max-width:100%;overflow-x:hidden}body{margin:0;background:var(--paper);color:var(--ink);font:16px/1.7 system-ui,sans-serif}a{color:inherit}
        .wrap{width:calc(100% - 36px);max-width:920px;min-width:0;margin-inline:auto}.nav{display:flex;justify-content:space-between;align-items:center;gap:18px;padding:18px 0}
        .brand{display:flex;align-items:center;gap:10px;font-weight:850;text-decoration:none;line-height:1.2}.brand img{width:48px;height:48px;object-fit:contain}
        .cta{background:var(--brand);color:white;padding:10px 16px;border-radius:10px;text-decoration:none;font-weight:700;white-space:nowrap}
        .hero{padding:70px 0;background:linear-gradient(135deg,#fff0e9,#fff8dc)}h1{font-size:clamp(38px,7vw,64px);line-height:1.05;letter-spacing:-2px;margin:0;max-width:850px;overflow-wrap:break-word}
        .lead{font-size:20px;color:var(--muted);max-width:760px;overflow-wrap:anywhere}.content{padding:65px 0}.content h2{margin-top:42px;font-size:30px;line-height:1.2;overflow-wrap:anywhere}.content p{max-width:100%;overflow-wrap:anywhere}
        .content p,.content li{color:#5d504a}.grid{display:grid;grid-template-columns:repeat(2,1fr);gap:18px}.card{background:white;border:1px solid var(--line);border-radius:16px;padding:22px}
        .band{margin-top:55px;background:var(--ink);color:white;border-radius:22px;padding:32px}.band p{color:#eaded7}.button{display:inline-block;margin-top:10px;background:var(--accent);color:var(--ink);padding:12px 18px;border-radius:11px;text-decoration:none;font-weight:800}
        @media(max-width:650px){.wrap{width:calc(100% - 48px)}.nav{gap:8px}.brand{min-width:0;font-size:13px}.brand img{width:40px;height:40px;flex:none}.cta{padding:9px 11px;font-size:13px}.hero{padding:48px 0}h1{font-size:clamp(32px,8vw,42px);letter-spacing:-1.2px;overflow-wrap:anywhere}.lead{font-size:18px}.grid{grid-template-columns:1fr;min-width:0}.card{min-width:0}.content{padding:42px 0}.content h2{font-size:26px}}
    </style>
</head>
<body>
    <nav class="wrap nav" aria-label="Navegação principal">
        <a class="brand" href="/" aria-label="Cardápio Digital BH, página inicial"><img src="{{ asset('images/cardapio-digital-bh-logo.svg') }}" alt=""><span>Cardápio Digital BH</span></a>
        <a class="cta" href="{{ route('register') }}">Criar grátis</a>
    </nav>
    <header class="hero"><div class="wrap"><h1>Cardápio digital em Belo Horizonte, leve e fácil de atualizar.</h1><p class="lead">Substitua PDFs pesados e cardápios impressos por um menu online com QR Code. Seus clientes acessam pelo celular, sem instalar aplicativo.</p></div></header>
    <main class="wrap content">
        <h2>Uma experiência melhor para clientes e equipe</h2>
        <p>O Cardápio Digital BH ajuda restaurantes, bares, lanchonetes, cafés e outros negócios de alimentação a manter produtos, preços e disponibilidade atualizados. Cada estabelecimento recebe um endereço exclusivo e cada mesa pode ter seu próprio QR Code.</p>
        <div class="grid">
            <article class="card"><h3>Atualização em tempo real</h3><p>Pause itens indisponíveis, ajuste preços e organize categorias sem reimprimir o cardápio.</p></article>
            <article class="card"><h3>Sem download</h3><p>O cliente aponta a câmera para o QR Code e abre o cardápio diretamente no navegador.</p></article>
            <article class="card"><h3>Pedidos por mesa</h3><p>O QR identifica a mesa, envia o pedido para a operação e mantém a comanda organizada.</p></article>
            <article class="card"><h3>Cozinha e caixa</h3><p>A equipe acompanha o preparo e registra pagamentos com acessos separados por função.</p></article>
        </div>
        <h2>Por que usar um cardápio por QR Code?</h2>
        <p>Além de reduzir custos com reimpressão, um menu digital facilita promoções sazonais, evita informações antigas e torna a escolha mais rápida. O estabelecimento mantém controle do catálogo e pode evoluir para pedidos, comandas e operação completa no mesmo sistema.</p>
        <h2>Feito em Belo Horizonte para negócios de alimentação</h2>
        <p>A plataforma combina atendimento local com tecnologia web moderna. O cardápio funciona em qualquer cidade, mas preserva a experiência e a proximidade de uma solução criada em Belo Horizonte.</p>
        <section class="band"><h2>Crie agora seu cardápio digital</h2><p>Abra sua conta gratuita, escolha o endereço do estabelecimento e publique seus primeiros itens.</p><a class="button" href="{{ route('register') }}">Começar grátis</a></section>
    </main>
</body>
</html>
