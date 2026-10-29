@extends('marketing.layout')
@section('title', 'Política de Privacidade | Cardápio Digital BH')
@section('description', 'Como a plataforma Cardápio Digital BH trata dados pessoais e quais preferências estão disponíveis.')
@section('canonical', 'https://cardapiodigitalbh.com.br/politica-de-privacidade')
@section('content')
<main class="legal-page">
    <div class="marketing-wrap legal-wrap">
        <div class="legal-intro reveal">
            <span class="eyebrow eyebrow--red">informações importantes</span>
            <h1>Política de privacidade</h1>
            <p>Transparência sobre os dados usados para operar a plataforma e os cardápios digitais.</p>
            <small>Última atualização: 29 de outubro de 2026 · Versão {{ config('legal.privacy_version') }}</small>
        </div>
        <article class="legal-card reveal">
            <h2>Quem opera o serviço</h2>
            <p>O Cardápio Digital BH é um serviço de 12.277.359 MARCIO STUART OLIVEIRA. Para assuntos de privacidade, use <a href="mailto:privacidade@cardapiodigitalbh.com.br">privacidade@cardapiodigitalbh.com.br</a>. O estabelecimento que publica um cardápio também define informações e canais usados na própria operação.</p>

            <h2>Dados tratados</h2>
            <p>Para contas de lojistas e equipe, tratamos nome, e-mail, dados do estabelecimento, informações de autenticação, aceite dos documentos e registros técnicos necessários à segurança e ao suporte.</p>
            <p>Em pedidos, podem ser tratados nome, telefone, e-mail associado à conta do consumidor, endereço de entrega, itens e adicionais, observações, canal, valores, consentimento para mensagens e estados do pedido e do pagamento. Contas de consumidores podem manter endereços e histórico para facilitar pedidos em diferentes estabelecimentos.</p>
            <p>O estabelecimento pode enviar imagens e informações comerciais para compor o cardápio. Esses materiais ficam associados ao respectivo estabelecimento.</p>

            <h2>Localização em pedidos por mesa</h2>
            <p>Quando o estabelecimento ativa a verificação de proximidade, o navegador pode solicitar localização e precisão aproximadas para conferir se o pedido está dentro do raio configurado. Essa coordenada é usada nessa verificação e não é salva como campo do pedido. Se a permissão não funcionar, a equipe pode fornecer um código temporário de uso único para uma mesa.</p>

            <h2>Mensagens e notificações</h2>
            <p>Se o consumidor autorizar mensagens por WhatsApp, a plataforma pode enviar o telefone e o conteúdo da mensagem à Evolution API configurada para a operação. O texto usa o modelo definido pelo estabelecimento e pode incluir nome do cliente, número do pedido, nome do estabelecimento e valor. A mensagem não é enviada quando falta o consentimento registrado.</p>
            <p>Quando um usuário da equipe ativa alertas de novos pedidos, a inscrição do navegador e suas chaves são armazenadas criptografadas e associadas ao usuário e estabelecimento. O alerta não inclui nome, telefone, endereço ou itens do cliente. O usuário pode desativar a inscrição no painel ou nas permissões do navegador.</p>

            <h2>Pagamentos online</h2>
            <p>Quando o consumidor escolhe pagamento online, compartilhamos com o Mercado Pago os dados necessários para criar o checkout, como identificador do pedido, estabelecimento, valor e, quando disponível, e-mail do pagador. O consumidor informa os dados de PIX ou cartão diretamente ao Mercado Pago; a plataforma não armazena os dados do cartão. Recebemos do provedor a confirmação e o estado do pagamento.</p>

            <h2>Cookies, medição e publicidade</h2>
            <p>Cookies essenciais mantêm sessão, autenticação e segurança. Google Analytics, Meta Pixel e o Google Tag Manager não são carregados antes da escolha do visitante. As preferências de medição e publicidade podem ser aceitas ou recusadas separadamente; o Tag Manager só é ativado quando ambas são aceitas, pois sua configuração pode conter tags das duas categorias.</p>
            <p>A escolha fica salva neste navegador por até 180 dias. Use o botão “Preferências de cookies”, disponível nas páginas, para revisar ou retirar sua escolha. Recusar não impede o uso do serviço.</p>

            <h2>Compartilhamento e armazenamento</h2>
            <p>Os dados são processados em infraestrutura de hospedagem, banco de dados e armazenamento de objetos usados para operar a plataforma. Mercado Pago, Evolution API, Google, Meta e serviços de notificação do navegador recebem dados somente nas situações descritas nesta política e conforme os recursos ativados. O bucket de arquivos da plataforma é privado, com acesso separado por estabelecimento.</p>
            <p>Não vendemos dados pessoais. Aplicamos controles de acesso e medidas técnicas de proteção. Nenhum sistema conectado à internet é completamente imune a riscos.</p>

            <h2>Finalidades e direitos</h2>
            <p>Usamos os dados para autenticar usuários, prestar e proteger o serviço, processar pedidos e pagamentos, enviar comunicações autorizadas, oferecer suporte e cumprir obrigações aplicáveis.</p>
            <p>O titular pode solicitar confirmação, acesso, correção ou eliminação quando aplicável, além de exercer os demais direitos previstos na legislação. Envie a solicitação para <a href="mailto:privacidade@cardapiodigitalbh.com.br">privacidade@cardapiodigitalbh.com.br</a>.</p>

            <h2>Retenção e alterações</h2>
            <p>Conservamos cada categoria de dados pelo tempo necessário à finalidade e às obrigações aplicáveis. Os prazos podem variar conforme o registro e a natureza do pedido. Esta política pode ser atualizada; a versão vigente permanecerá nesta página.</p>
        </article>
    </div>
</main>
@endsection
