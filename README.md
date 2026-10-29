# Cardapio Digital BH

Plataforma independente, multi-tenant, para cardapios digitais, atendimento de salao, comandas, caixa e cozinha. O legado Escolha TOP nao e utilizado nem alterado por este projeto.

## Ambiente local

Copie `.env.example` para `.env`, execute `php artisan key:generate`, `php artisan migrate` e `npm run dev`.

## Publicacao no Portainer

Os passos abaixo descrevem a primeira instalacao. Se ja existir uma stack `cardapiodigitalbh` em uso, antes de importar ou reaplicar o Compose registre a imagem/tag atual, replicas, redes, volumes e variaveis protegidas; confirme backup recente do PostgreSQL e MinIO e plano de retorno. Nao recrie volumes nem substitua a stack ativa sem validar esses pontos. Nunca altere stacks de outros projetos.

1. Crie o banco PostgreSQL `cardapiodigitalbh` e o bucket MinIO `cardapiodigitalbh`.
2. No repositorio clonado pelo Portainer, copie `.env.production.example` para `.env` e preencha os valores sem inclui-los no Git.
3. Crie a rede externa `traefik` se ela ainda nao existir e confirme que os nomes `postgres`, `redis` e `minio` sao acessiveis pela stack.
4. Publique `docker-compose.yml` pelo recurso Git do Portainer.
5. Na primeira instalacao, execute `php artisan migrate --force` e `php artisan storage:link` no container `app`. Em cada release que inclua migration, aplique `php artisan migrate --force` com a nova imagem antes de liberar as novas funcoes; migrations devem manter compatibilidade com a revisao anterior durante o rollout.
6. Para habilitar Web Push, depois de implantar o codigo, execute `sh deploy/setup-webpush.sh` uma vez no host Swarm e preserve o par VAPID. O comando grava as chaves diretamente no `.env` protegido sem exibir a privada. Reaplique somente a stack `cardapiodigitalbh` para carregar as variaveis.
7. Verifique `https://cardapiodigitalbh.com.br/up` e depois um subdominio de estabelecimento.

O Traefik recebe o dominio raiz e os subdominios. Caddy atende HTTP internamente e o PHP-FPM executa Laravel. O worker processa filas no Redis.

Para a Evolution API, configure somente no ambiente de produção: `EVOLUTION_API_URL`, `EVOLUTION_API_KEY` e `EVOLUTION_WEBHOOK_SECRET`. O webhook deve apontar para `https://cardapiodigitalbh.com.br/webhooks/evolution` e enviar o segredo no cabeçalho `X-Webhook-Secret`.

As credenciais de Mercado Pago e Evolution permanecem somente no ambiente protegido. O Web Push guarda endpoint e chaves por dispositivo criptografados com `APP_KEY`; nao altere `APP_KEY` nem regenere as chaves VAPID sem planejar a invalidacao das inscricoes existentes.

## Impressao termica no Windows

O agente local usa o driver instalado no Windows e atende impressoras USB, incluindo modelos Epson TM-T20 com o driver oficial. Pareamento, seleção de fila e instalação estão descritos em [`printer-agent/README.md`](printer-agent/README.md). Compatibilidade com cada combinação de impressora/driver deve ser confirmada em um piloto físico.

## Configuracao simples de homologacao

1. Na VPS, abra somente o `.env` da stack `cardapiodigitalbh` e copie os campos comentados de `.env.production.example` que ainda nao existirem. Nao substitua os valores atuais de banco, `APP_KEY`, SMTP ou MinIO.
2. Para Evolution, preencha URL HTTPS, chave da API e um segredo longo; configure o webhook para `https://cardapiodigitalbh.com.br/webhooks/evolution` com o mesmo segredo no cabecalho `X-Webhook-Secret`.
3. No Mercado Pago, crie uma aplicacao de teste, habilite PKCE, cadastre exatamente a URL de retorno indicada no `.env` e configure o evento `Pagamentos` para `https://cardapiodigitalbh.com.br/webhooks/mercado-pago`.
4. Copie o ID da aplicacao, o segredo da aplicacao e o segredo do webhook para o `.env`. Mantenha `MERCADOPAGO_TEST_MODE=true`.
5. Reaplique somente a stack `cardapiodigitalbh`. Depois, conecte uma conta de vendedor de teste em **Painel → Pagamentos** e faça um pedido de teste.
6. Antes de habilitar cobranças reais, confirme checkout PIX/cartao, webhook assinado, pedido na cozinha, expiracao e cancelamento.

O cadastro e o acesso ao painel registram o aceite e a versão dos Termos de Uso e da Política de Privacidade. Ao alterar um documento, atualize a variável correspondente (`CARDAPIO_TERMS_VERSION` ou `CARDAPIO_PRIVACY_VERSION`) no `.env` protegido; usuários existentes verão a confirmação antes de continuar. Os valores padrão estão em `config/legal.php`.

Nao envie chaves, tokens ou o conteudo do `.env` pelo chat. Se um campo ja existir, atualize o valor dessa linha; nao crie uma segunda linha com o mesmo nome.

## Validacao

```
php artisan test
npm run build
```
