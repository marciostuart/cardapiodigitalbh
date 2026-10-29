@extends('marketing.layout')
@section('title', 'Termos de Uso | Cardápio Digital BH')
@section('description', 'Termos e condições de uso da plataforma Cardápio Digital BH.')
@section('canonical', 'https://cardapiodigitalbh.com.br/termos-de-uso')
@section('content')
<main class="legal-page">
    <div class="marketing-wrap legal-wrap">
        <div class="legal-intro reveal">
            <span class="eyebrow eyebrow--red">informações importantes</span>
            <h1>Termos de uso</h1>
            <p>Leia as regras que orientam o uso da plataforma Cardápio Digital BH.</p>
            <small>Última atualização: 29 de outubro de 2026 · Versão {{ config('legal.terms_version') }}</small>
        </div>
        <article class="legal-card reveal">
            <h2>1. Serviço</h2>
            <p>O Cardápio Digital BH oferece ferramentas online para criação de cardápios, QR Codes, pedidos, comandas, caixa, cozinha e apoio à operação de estabelecimentos de alimentação. Recursos como pagamentos online, notificações, localização e impressão dependem da configuração do estabelecimento, dos dispositivos e de serviços de terceiros. Os recursos e limites podem variar conforme o plano.</p>

            <h2>2. Conta e aceite</h2>
            <p>Para criar uma conta, o responsável deve fornecer informações corretas e proteger suas credenciais. O cadastro exige aceite destes Termos e ciência da <a href="{{ route('marketing.privacy') }}">Política de Privacidade</a>. Registramos a versão e a data do aceite. Quando os documentos forem atualizados, poderemos solicitar nova confirmação antes do acesso ao painel.</p>

            <h2>3. Conteúdo e operação do estabelecimento</h2>
            <p>O estabelecimento é responsável por preços, descrições, imagens, disponibilidade dos produtos, informações comerciais, atendimento ao consumidor, preparo e entrega dos pedidos. O usuário declara possuir os direitos necessários para publicar o conteúdo e deve manter os dados operacionais atualizados.</p>

            <h2>4. Pedidos e pagamentos de terceiros</h2>
            <p>Quando o pagamento online estiver habilitado, o pedido poderá ser criado como aguardando pagamento. Ele só segue para a operação do estabelecimento após a confirmação recebida do provedor de pagamento. O cliente informa os dados de PIX ou cartão diretamente no ambiente desse provedor; a plataforma não armazena os dados do cartão. Regras e disponibilidade do pagamento também dependem do provedor escolhido.</p>

            <h2>5. Disponibilidade e uso adequado</h2>
            <p>Buscamos manter o serviço disponível e seguro, mas manutenções, falhas de conexão, dispositivos, provedores externos ou eventos fora do nosso controle podem causar interrupções. É proibido explorar falhas, automatizar acessos abusivos, publicar conteúdo ilegal ou prejudicar outros usuários.</p>

            <h2>6. Planos e cancelamento</h2>
            <p>Condições, limites e preços são os apresentados no momento da contratação. O plano gratuito pode possuir limites próprios. Contratações online observam o direito de arrependimento e demais normas de defesa do consumidor quando aplicáveis. Não há cobrança recorrente habilitada sem a apresentação prévia das condições correspondentes.</p>

            <h2>7. Privacidade</h2>
            <p>O tratamento de dados pessoais está descrito na <a href="{{ route('marketing.privacy') }}">Política de Privacidade</a>.</p>

            <h2>8. Suporte e contato</h2>
            <p>Dúvidas podem ser enviadas para <a href="mailto:falecom@cardapiodigitalbh.com.br">falecom@cardapiodigitalbh.com.br</a>. Estes termos podem ser atualizados para refletir mudanças legais ou funcionais. A versão vigente permanecerá nesta página.</p>
        </article>
    </div>
</main>
@endsection
