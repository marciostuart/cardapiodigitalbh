# Memória do Projeto — Cardápio Digital BH

## Objetivo

Plataforma SaaS independente para restaurantes, bares e lanchonetes: cardápio digital via QR Code, pedidos por mesa, comandas, caixa e operação de cozinha. O legado Escolha TOP não é migrado nem alterado.

## Arquitetura

- Laravel 13, PHP 8.4, PostgreSQL, Redis e Caddy atrás do Traefik.
- Docker Swarm/Portainer na VPS; stack exclusiva `cardapiodigitalbh`.
- Domínio principal: `cardapiodigitalbh.com.br`; subdomínio por estabelecimento: `{slug}.cardapiodigitalbh.com.br`.
- Multi-tenant por `tenant_id`; `Venue` representa o estabelecimento operacional.
- Serviços: `cardapiodigitalbh_app`, `cardapiodigitalbh_web`, `cardapiodigitalbh_worker`.

## Recursos atuais

- Landing institucional, login e cadastro de estabelecimento.
- Cadastro cria Tenant, Venue e usuário `owner`; plano inicial `free`.
- Cadastro enfileira e-mail de boas-vindas; entrega real depende do SMTP configurado no ambiente.
- Owner da plataforma edita assunto, título, mensagem, botão e rodapé dos e-mails transacionais em `/admin/emails`; o HTML responsivo e a identidade visual permanecem padronizados.
- Owner de plataforma (`platform_owner`): `/admin`, lista estabelecimentos, altera plano e pode impersonar o owner do cliente.
- Owner de estabelecimento: dashboard, CRUD do catálogo, mesas e QR Code SVG imprimível por mesa.
- Cardápio público no domínio raiz do tenant (`https://{slug}.cardapiodigitalbh.com.br`) e por QR (`/m/{token}`).
- Pedido público por QR com carrinho; preço e total sempre recalculados no servidor.
- Comandas podem ser coletivas por mesa ou individuais por nome informado no pedido.
- Operação: mapa de mesas, KDS visual, avanço de itens, comandas, pagamentos manuais e liberação de mesa.
- Equipe: owner cria acessos de garçom, cozinha e caixa; cada função possui rotas limitadas ao trabalho correspondente.
- Owner da plataforma pode ativar/suspender estabelecimentos; a suspensão bloqueia login, cardápio público e novos pedidos QR, mantendo o acesso por impersonação para suporte.
- Salão, KDS e caixa possuem atualização automática; uma mesa só é liberada quando a última comanda individual é quitada.
- SEO: preservar as URLs indexadas `/cardapio-digital-em-belo-horizonte`, `/termos-de-uso` e `/politica-de-privacidade`; sitemap e robots publicados no domínio principal.
- Owner da plataforma possui dashboard executivo próprio em `/owner`, separado da gestão de estabelecimentos em `/admin`; KPIs, falhas e auditoria são persistidos.
- Planos são entidades administráveis, com preço, limites e capacidades; os slugs iniciais continuam `free`, `essencial` e `pro`.
- Impersonação de cliente registra início/fim e exibe um banner permanente de modo suporte.
- Cada estabelecimento escolhe ordenação manual ou por popularidade dentro de cada categoria. O padrão é `popularity`, calculado pela quantidade vendida em pedidos aceitos/concluídos; `position` desempata.
- Evolution API v2.3.7: uma instância isolada por tenant, conexão por QR, consulta de estado, outbox idempotente, retentativas e templates globais com personalização por estabelecimento.
- O Owner da plataforma dispõe de `Reset Whats`, que destrói somente o nome de instância associado ao tenant selecionado e registra auditoria.
- Estoque suporta produto acabado, insumos, ficha técnica, reserva transacional, consumo idempotente, devolução antes do preparo, ajustes e alertas mínimos.
- Funcionamento automático suporta múltiplos intervalos, horários após meia-noite e abertura/fechamento manual temporário.
- Exceções de calendário substituem os horários semanais em feriados e datas especiais, com fechamento integral ou intervalo próprio.
- Geofence opcional valida latitude/longitude no servidor e aceita raio entre 20 e 1.000 metros.
- Cardápio público aceita mesa, retirada e delivery; inclui clientes, endereço ViaCEP, regiões/taxas, pedido mínimo, cupons, troco e acompanhamento por token público.
- Gestão de pedidos aplica uma máquina de estados progressiva e dispara eventos transacionais do WhatsApp quando houver consentimento.
- Produtos aceitam grupos de variações/adicionais com seleção mínima e máxima. O preço e o pertencimento de cada opção são validados no servidor e gravados como snapshot no item do pedido.
- Mídias usam MinIO/S3 privado no bucket exclusivo `cardapiodigitalbh`, com credencial limitada ao bucket e prefixos `tenants/{tenant_id}/logos`, `products` e `banners`. Nomes de arquivo usam slug SEO mais sufixo aleatório; a aplicação publica as imagens pelo subdomínio correto com cache.
- Toda imagem de produto, logo ou banner é validada, redimensionada proporcionalmente para no máximo 1.400 px e convertida para WebP. A compressão inicia em 68% e reduz até 55% quando necessário, visando cerca de 140 KB; o original não é persistido. O limite da aplicação é 12 MB/24 megapixels e o PHP usa `upload_max_filesize=16M` e `post_max_size=20M`.
- Produtos possuem editor dedicado e responsivo, com prévia/arrastar-e-soltar da foto, dados comerciais, estoque, visibilidade e variações em uma única jornada.
- Cada produto aceita uma foto de destaque e até quatro fotos de galeria (cinco no total), todas isoladas por tenant e submetidas ao mesmo pipeline WebP adaptativo/1.400 px.
- A vitrine pública usa largura de até 1.400 px, busca, navegação por categorias e carrosséis responsivos com três produtos visíveis no desktop. A foto abre a página individual; `Comprar` adiciona diretamente quando não há variações e abre a personalização quando houver grupos.
- Identidade visual, logo, cores e banners são administrados em Configurações. O slug pode ser alterado quando disponível, mas exige confirmação explícita sobre a quebra de URLs e QR Codes antigos.

## Padrões obrigatórios

- Toda consulta/mutação de cliente deve ser isolada por `tenant_id`/`venue_id`.
- Rotas internas exigem autenticação; rotas públicas não expõem IDs sensíveis.
- Nunca tocar em stacks, banco, rede ou volumes de outros projetos da VPS.
- Todo deploy deve: testes locais, build da imagem, migration quando aplicável, atualização apenas da stack e smoke test HTTP.
- Segredos ficam somente em `/opt/cardapiodigitalbh/.env`; nunca Git/logs/documentação.
- Atualizar este arquivo em mudanças arquiteturais, incidentes e decisões relevantes.
- Toda integração externa deve usar outbox/idempotência; falha de mensagem nunca reverte pedido ou pagamento.
- Nunca desabilitar validação TLS. A chave global da Evolution permanece apenas no ambiente de produção.
- `Reset Whats` sempre recebe um `Tenant` autorizado, usa exclusivamente o `instance_name` persistido desse tenant e jamais executa exclusão em lote.
- Estoque deve ser alterado dentro de transação e com bloqueio pessimista; reprocessamento de evento não pode baixar duas vezes.

## Produção e deploy

- Diretório VPS: `/opt/cardapiodigitalbh`.
- Rede compartilhada somente para conectividade: `360Network`.
- Banco exclusivo: `cardapiodigitalbh`; usuário exclusivo criado na VPS.
- Traefik usa `letsencryptresolver` para o domínio principal.
- O wildcard DNS está com proxy Cloudflare e SSL `Full`, fornecendo HTTPS confiável para `{slug}.cardapiodigitalbh.com.br` sem modificar o Traefik global.
- SMTP próprio configurado somente no `.env` de produção; envio real do template HTML validado em 2026-08-20.
- Caddy serve assets compilados. A causa de CSS ausente foi volume do host mascarando `public/build`; solução permanente: imagem `web` no Dockerfile copia assets da imagem `app`.

## Incidentes e correções

| Data | Problema | Correção |
|---|---|---|
| 2026-08-20 | Rotas removidas pelo scaffold de auth | Rotas operacionais restauradas e cobertas por testes. |
| 2026-08-20 | CSS/JS ausentes no Caddy | Assets sincronizados; Dockerfile evoluído para imagem web própria. |
| 2026-08-20 | `/admin` retornou 500 | Controller Admin não havia sido copiado para a VPS; diretório e imagem corrigidos. |
| 2026-08-20 | `/menu` no domínio principal retornou 404 | Menu depende de tenant; prévia deve usar slug/subdomínio ou token QR. |
| 2026-08-20 | `/painel/cardapio` retornou 500 | View fechava `@forelse` com `@endforeach`; corrigida e coberta por teste funcional. |
| 2026-08-20 | HTTPS do subdomínio apresentou certificado não confiável | Wildcard DNS passou pelo proxy Cloudflare em modo SSL `Full`; certificado público validado sem alterar o Traefik global. |
| 2026-08-20 | Cookies de sessão em produção sem atributo `Secure` | `SESSION_SECURE_COOKIE=true` definido no ambiente de produção; Caddy recebeu cabeçalhos defensivos. |
| 2026-08-20 | Quitação individual liberava mesa com outras comandas abertas | Caixa agora verifica outras comandas antes de alterar a mesa para disponível. |
| 2026-08-20 | Suspensão administrativa não era aplicada ao tenant | Login, domínio público e pedidos QR agora respeitam o status do estabelecimento. |
| 2026-08-20 | Upload de produto acima de 8 MB era rejeitado antes de chegar ao Laravel | Limites do PHP elevados com margem controlada; validação em 12 MB e pipeline GD/WebP 75% adicionados à imagem Docker. |
| 2026-08-20 | Upload era concluído, mas a rota pública da imagem retornava 404 | O parâmetro de domínio `{subdomain}` estava deslocando os argumentos do controller invocável; assinatura corrigida e coberta por teste de streaming privado. |
| 2026-08-20 | Catálogo de demonstração precisava reproduzir o cardápio autorizado de Paulin Lanches | Importação idempotente executada no estabelecimento existente `paulinlanches`, visitando 83 páginas de produtos autorizadas, preservando 8 categorias, preços vigentes e variações. As imagens foram processadas no pipeline WebP/MinIO do tenant. Evidências: 83 produtos, 83 imagens, 173 opções; cardápio e imagem WebP retornaram HTTP 200. |
| 2026-08-20 | Estabelecimentos precisavam gerir descoberta orgânica e mensuração sem inserir scripts livres | Configurações agora armazenam título SEO, meta descrição, palavras-chave, imagem social WebP/MinIO, Google Analytics e Pixel da Meta por estabelecimento. O Owner controla um único ID GTM validado para a plataforma. Logo do estabelecimento é usada como favicon WebP e ícone de tela inicial. |
| 2026-08-20 | Imagens públicas passavam pelo grupo web com sessão, elevando latência e impedindo cache do navegador | A rota `/media` tornou-se pública e sem estado, mantendo somente o isolamento do tenant. As respostas agora possuem cache público imutável por um ano; o PWA ainda guarda imagens já acessadas para navegação posterior sem nova execução do PHP. |
| 2026-08-20 | Pedido concluído não oferecia uma próxima ação clara e sua tela tinha identidade genérica | A confirmação passou a oferecer acompanhamento ou continuidade no cardápio. A tela pública do pedido usa logo, cores primária/secundária, favicon, PWA e links seguros do próprio estabelecimento. |
| 2026-08-20 | O painel de pedidos permitia selecionar uma etapa inválida e retornava uma página técnica 422 | Cada pedido apresenta apenas as próximas transições permitidas. Uma atualização concorrente retorna mensagem em português, preserva o pedido e não expõe erro técnico. |
| 2026-08-20 | Atendentes precisavam interpretar um seletor de etapas antes de operar um pedido | O seletor foi substituído por ações diretas: Aceitar/Recusar, Em produção/Cancelar, Pedido pronto/Cancelar, Saiu para entrega/Concluído/Cancelar. Recusar ou cancelar abre modal e exige a confirmação final. |
| 2026-08-20 | Atendimento precisava consultar e corrigir pedidos sem perder rastreabilidade operacional | Todo pedido ganhou página de detalhes. Pedidos abertos permitem notas gerais, de cozinha e de entrega, ajuste de quantidade, itens e opcionais com total recalculado e ajuste de estoque/reserva; concluídos e cancelados são somente leitura. |
| 2026-08-20 | A vitrine desktop não comunicava claramente que havia mais produtos na categoria | O carrossel passou a usar flexibilidade controlada: até quatro cards completos no desktop e uma prévia lateral do quinto; as setas avançam a lista. No celular permanece um card principal e parte do próximo. |
| 2026-08-20 | As setas do carrossel ficavam distantes dos produtos e não indicavam corretamente os limites da navegação | Os controles agora ficam sobre as extremidades da linha. Eles avaliam a rolagem real: no início só surge avançar, durante a navegação surgem os dois sentidos e, no fim, só voltar permanece visível. |
| 2026-08-20 | A página de produto e o acompanhamento não ofereciam a mesma navegação da vitrine | Cabeçalho, busca, categorias e rodapé foram centralizados em componentes públicos. Produto passa a sugerir itens comprados junto com base em pedidos válidos, com itens complementares como contingência. |
| 2026-08-21 | Avaliação de desempenho apontou peso e trabalho desnecessário na primeira dobra, além de oportunidade de descoberta por IA | Somente a primeira imagem recebe prioridade alta; categorias fora da dobra usam contenção de renderização e o GTM passa a carregar na primeira interação ou em ociosidade. Cada domínio público expõe `llms.txt` em Markdown e o cardápio publica dados estruturados de restaurante. |
| 2026-08-21 | A prévia lateral e o degradê do carrossel reduziram a clareza visual no desktop | O desktop exibe quatro cards completos. As setas ficam fora da coluna dos produtos, usam a cor primária do estabelecimento e a categoria pode ser expandida por “Ver tudo →” em grade sem rolagem horizontal. |
| 2026-08-21 | Consumidores precisavam de acesso persistente entre estabelecimentos sem misturar contas de lojistas ou o CRM de cada tenant | `consumer_accounts` e endereços globais passaram a autenticar por telefone e senha em todos os subdomínios. O checkout cria ou acessa a conta antes do primeiro envio, usa ViaCEP, preserva carrinho e vincula pedidos à conta global e ao cliente local do estabelecimento. A recuperação ocorre por e-mail, sem revelar a existência da conta. |

## Estado consolidado em 2026-08-20 (snapshot historico; producao atual nao confirmada)

- Repositório: `github.com/marciostuart/cardapiodigitalbh`, branch `main`. Todo código de produto deve ser versionado; não incluir `.env`, chaves, tokens, sessões ou URLs assinadas.
- Produção: VPS em `/opt/cardapiodigitalbh`, stack Swarm isolada `cardapiodigitalbh`, serviços `app`, `web` (Caddy) e `worker`. A rede `360Network` é somente a conexão compartilhada com o proxy; nunca alterar stacks, rede, volumes, banco ou serviços de outros projetos.
- Última publicação registrada no snapshot histórico: tag/imagens `6719fe9`, serviços `1/1`. A stack atual não foi confirmada. O processo de release registrado é: validação local, commit/push, pacote da revisão, envio para `releases/`, extração somente em `/opt/cardapiodigitalbh`, construção das imagens e atualização apenas desta stack.
- Arquitetura: Laravel, PostgreSQL exclusivo, Redis, fila em worker, MinIO/S3 privado por tenant e Caddy. As mídias públicas são entregues por `/media` sem sessão, com isolamento de subdomínio/tenant, cache público de um ano e cache complementar no PWA. Não substituir MinIO por armazenamento local sem antes projetar persistência, backup, escalabilidade e rollback.
- Domínios: o institucional é `cardapiodigitalbh.com.br`; cada cardápio usa exclusivamente `https://{slug}.cardapiodigitalbh.com.br`. Alterar slug exige aviso de quebra de links/QR Codes antigos. O estabelecimento demonstrativo autorizado usa `paulinlanches`.
- Identidade da vitrine: cores e logo do estabelecimento são fonte da interface pública, favicon e PWA. Cabeçalho limpo com faixa superior na cor primária, saudação à esquerda, estado aberto à direita, logo/nome e busca; sem pedido mínimo, compartilhamento ou sacola no cabeçalho. O mesmo cabeçalho e trilho de categorias devem estar em todas as páginas públicas. Categorias ficam em trilho horizontal com “Todas”; títulos são sobrepostos e centralizados nas fotos com sombra. O botão fixo do carrinho é o acesso à sacola. Há rodapé global com direitos autorais e link rastreável para a 360BH.
- Produtos: imagem destaque mais galeria de até quatro imagens adicionais; uploads convertidos para WebP, máximo 1.400 px e compressão adaptativa 68% a 55%, meta de cerca de 140 KB. Produtos com grupos obrigatórios abrem personalização; ao atingir o máximo de escolhas, a opção mais antiga é desmarcada automaticamente. Ordenação padrão é por popularidade dentro da categoria, com alternativa manual.
- Pedido público: retirada, mesa e delivery; nome/telefone opcionais fora de comanda de mesa, WhatsApp obrigatório apenas com consentimento. CEP usa ViaCEP. Após envio, modal oferece acompanhar ou continuar no cardápio; acompanhamento mantém identidade visual do tenant, recarrega a cada 20 segundos e permite voltar a comprar.
- Conta de consumidor: autenticação independente de lojistas, por telefone brasileiro normalizado e senha forte; sessão compartilhada por `.cardapiodigitalbh.com.br`, com endereço padrão global e recuperação de senha por e-mail. A conta sincroniza nome, telefone e e-mail ao CRM do tenant somente quando realiza um pedido nele.
- PWA: manifesto e service worker são específicos do tenant. O app guarda home e mídias acessadas; uma nova versão apresenta “Atualizar”. A verificação periódica é a cada cinco minutos para equilibrar atualização e consumo de recursos.
- Descoberta por IA: `llms.txt` é um índice Markdown complementar para agentes e não substitui SEO técnico, páginas rastreáveis ou dados estruturados. Cada subdomínio tem orientação própria; o domínio principal também expõe um índice institucional. Não expor ferramentas que criem pedidos, cobrem valores ou alterem dados sem confirmação explícita de pessoa usuária.
- Administração: `/owner` é o painel executivo da plataforma; `/admin` é a gestão de tenants/planos/impersonação e suporte. O painel do lojista usa `/painel/*`. O Owner pode redefinir somente a instância WhatsApp do tenant selecionado. GTM global é configurado pelo Owner; o lojista usa campos validados para Analytics e Pixel da Meta, nunca scripts arbitrários.
- Catálogo de demonstração: importação autorizada de Paulin Lanches concluída com 83 produtos, 8 categorias, 83 imagens e 173 opções. Tratar como dados de demonstração do tenant existente; manter consistência e não importar novamente sem necessidade.
- Qualidade: o último conjunto completo de testes registrado antes desta rodada passou com 84 testes e 295 asserções. A rodada atual repetiu verificações estáticas/build/migrations, mas ainda não repetiu a suite completa nem comprovou publicação.

## Checklist mestre de conclusão do projeto (revisado em 2026-10-29)

Este checklist substitui pendências antigas que ficaram desatualizadas. “Implementado” descreve o código; “validar” exige evidência no ambiente real; nenhuma integração é considerada homologada somente por testes simulados.

### Decisões confirmadas pelo responsável

- Verificação de e-mail obrigatória antes do acesso ao painel.
- Pagamento online cria o pedido com estado aguardando pagamento e oferece checkout/QR antes de enviar para a cozinha; o piloto usará PIX/cartão pelo Mercado Pago.
- Impressão inicial: USB em computador Windows, começando por Epson TM-T20 e compatibilidade via drivers instalados no Windows.
- Geofence: proprietário autoriza exceção por prazo curto (15 minutos, código de uso único).
- O `.env` local agora contém campos vazios e instruções para Evolution, VAPID e Mercado Pago; nenhuma credencial de sandbox foi configurada localmente. Os valores da VPS só poderão ser verificados após restabelecer SSH seguro; o responsável preencherá segredos sem enviá-los pelo chat.

### Núcleo do produto

- [x] SaaS multi-tenant, catálogo, pedidos, mesas/comandas, caixa, cozinha, equipe, estoque, horários, delivery/retirada, cupons e relatórios estão implementados e cobertos por testes locais.
- [x] Banners já estão implementados: CRUD por estabelecimento, agendamento, ordenação, pausa, mídia isolada e renderização pública. Removido o registro incorreto de banners como planejados.
- [x] PWA básico já existe: manifesto por tenant, service worker, cache da home/mídias e atualização de versão. Isso não equivale a Web Push.
- [x] Implementada base de Web Push para novos pedidos: inscrição explícita por usuário/dispositivo, endpoints validados, chaves/endpoint criptografados, payload sem dados pessoais, fila idempotente em `outbound_messages`, remoção de endpoints expirados e worker de notificações. O envio não é bloqueante para o pedido.
- [x] Verificação de e-mail é obrigatória antes dos painéis para owner e equipe; o convite da equipe também dispara o e-mail de verificação. Entrega SMTP real ainda depende de homologação.
- [x] Cadastro exige aceite dos Termos e ciência da Política; cada usuário registra versão e data; documentos revisados passam a exigir novo aceite, inclusive das contas existentes. Testes cobrem bloqueio, gravação e idempotência; revisão jurídica e prazo/política operacional de retenção continuam pendentes.
- [x] `webpush:keys` gera e grava chaves VAPID no `.env` sem exibir a chave privada; o wrapper `deploy/setup-webpush.sh` usa a imagem ativa e monta somente o `.env` protegido. Deve rodar uma vez; preserve as chaves para não invalidar inscrições.
- [x] Composer atualizado para Laravel 13.39 e dependências corrigidas; `composer audit --locked` passou sem advisories em 2026-10-29.
- [x] Impressão de cartões QR usa `window.print()`; agente térmico Windows com spooler/driver, fila persistente, pareamento, deduplicação e reimpressão auditada está implementado localmente. Pacote self-contained Win-x64, instalador/atualizador por usuário e desinstalador foram validados; homologação física continua pendente.
- [x] Criado `php artisan media:audit --prefix=tenants`: modo somente leitura para volume, quantidade e idade das mídias; permite apenas `tenants` e `health`, sem exclusão. Execução local confirmou que o bucket não está configurado neste `.env`; a medição real depende da VPS.
- [x] Baseline antes das mudanças desta rodada: `php artisan test` (84 testes/295 assertions), views e build passaram; smoke HTTP anônimo respondeu 200. Isso não é resultado da versão atual.

- [x] Validações locais em 2026-10-29: `php artisan test` passou com 99 testes/439 asserções; 302 arquivos PHP de origem passaram em `php -l`; todas as migrations passaram em SQLite efêmero; `composer audit --locked`, build Vite no escopo limpo da imagem, compilação/limpeza Blade, lista de 144 rotas, `node --check public/js/staff-web-push.js`, `bash -n deploy/setup-webpush.sh`, publicação self-contained single-file Win-x64 do agente e `git diff --check` passaram. O executável foi gerado em `%TEMP%`, fora do Git. O Dockerfile usa estágio comum de dependências para incluir as views de paginação no build CSS e `.dockerignore` exclui `.env`, dependências, caches e arquivos de release. A imagem Docker não foi construída localmente porque o Docker CLI não está instalado. Os testes específicos cobrem aceite, expiração/uso único do override, criptografia/isolamento/idempotência do Web Push e bloqueio de analytics/ads sem consentimento, ausência de tags no painel e em rotas com tokens, consentimento no menu de mesa e leitura segura do token nas rotas QR por subdomínio e /m.
- [x] Reauditoria local em 2026-10-29: `npm audit` identificou cinco vulnerabilidades em dependências front-end. `npm audit fix` atualizou 11 pacotes dentro das faixas semver existentes; `npm ci` reproduziu o lockfile, `npm audit` retorna zero vulnerabilidades, `npm run build` passou com Vite 8.3.5 e `php artisan test` passou com 99 testes/439 asserções. Composer também permaneceu sem advisories. Isso não substitui validação autenticada no ambiente real.
- [x] Corrigida a fila operacional: `SyncMercadoPagoPayment` e `ExpireOnlinePayment` usam `payments`; novo serviço Swarm `payments-worker` consome essa fila. Compose validado com parser YAML e testes confirmam os jobs; falta publicar e verificar réplicas/execução na VPS.
- [x] `git archive` agora exclui `release-*.tar.gz` rastreados via `.gitattributes`; os arquivos históricos locais foram preservados e não entram no pacote da VPS.
- [x] O template `.env.production.example` orienta em passos curtos a gerar VAPID com `deploy/setup-webpush.sh`; o script escreve no `.env` protegido da VPS sem imprimir a chave privada. Execução e entrega real continuam pendentes da VPS e de um browser piloto.
- [x] Analytics/Meta Pixel ficam bloqueados sem escolha explícita; consentimentos por categoria ficam no cookie de primeira parte por até 180 dias, podem ser alterados pelo visitante e o GTM global exige ambas as categorias. O cookie foi excluído da criptografia Laravel para o JS poder gravá-lo; teste de feature usa cookie não criptografado como navegador real. Política ganhou os fluxos de pedidos, mensagens, pagamentos, push e cookies; versão subiu para `2026-10-29.1`, então usuários ativos precisarão aceitar novamente após implantação. Revisão jurídica e retenção seguem pendentes.
- [x] Criado `deploy/verify-stack-smoke.sh`, somente leitura, para conferir app/web/worker/payments-worker em 1/1, resposta HTTP não vazia em `/`, `/up`, `/favicon.ico`, logo e favicon WebP, e permissão de geolocalização. A sintaxe shell e os cenários simulado OK/asset vazio passaram; README corrigido para a rede externa `360Network`. Executar após a publicação.

### UX pública e publicação

- [x] Revisão responsiva em Firefox/Playwright: página pública em 390×844 sem overflow horizontal; 84 imagens carregadas, busca abre pelo botão, cabeçalho compacto permanece no topo e categorias seguem logo abaixo. Simulação controlada aplicando o CSS local com capa: uma busca; composição barra/capa/logo/nome/descrição/categorias; no scroll, capa/identidade somem e busca/categorias permanecem. A arte real de capa e páginas autenticadas ainda precisam de aceite no release/dispositivo.
- [x] Identidade institucional aplicada a partir da cópia sincronizada `D:\Márcio\Google Drive\Drive\360BH\Site Cardápio Digital BH`: SVG oficial e favicon entraram em `public/images`, o painel e autenticação usam a marca, botões/seleção ativa usam o vermelho `#EA4335`, amarelo `#FBBC05` foi mantido como acento e a página local de BH foi harmonizada. A vitrine pública continua usando a identidade configurada por cada lojista.
- [x] README de Portainer agora separa primeira instalação de stack existente e exige inventário, backup e plano de retorno antes de reaplicar, sem alterar stacks de outros projetos.
- [x] A alteração de `resources/views/public/partials/header.blade.php` foi revisada, commitada e enviada na branch `codex/cardapio-lancamento-20261029`; hash remoto conferido. Os 15 arquivos `release-*.tar.gz` foram inventariados sem extração: são arquivos TAR válidos de versões-fonte, sem `.env` ou `public/build`; 7 já estão rastreados e 8 seguem locais. Todos foram preservados e excluídos do contexto Docker.
- [x] Nesta rodada, GTM global ficou restrito às páginas públicas institucionais/cardápio após consentimento; GA/Meta diretos não são incluídos no painel nem em páginas de token. A rota QR de mesa corrigiu o parâmetro de token quando o subdomínio também está presente. Suíte atual: 99 testes/439 asserções; Node, Blade e diff checks passaram.
- [x] Corrigida localmente a política do Caddy: a resposta pública atual envia `geolocation=()` e impede o geofence; a versão da branch passa a permitir localização somente na própria origem. Validar o cabeçalho e a permissão do navegador após implantar.
- [x] O `public/favicon.ico` rastreado com 0 bytes foi substituído por um ICO válido de 16/32/48 px, convertido do favicon WebP oficial. O production check ainda retornou vazio no ambiente live antes desta publicação; o próximo deploy deve levar o arquivo.
- [ ] Confirmar qual commit está implantado na VPS e conferir serviços/health da stack exclusiva `cardapiodigitalbh`; publicar somente após revisão visual e validação, sem tocar em outros recursos. Rechecado em 2026-10-29: domínio raiz e `/up` retornaram HTTP 200; o SVG institucional retornou 404, `/favicon.ico` respondeu 200 com tamanho zero e `Permissions-Policy` ainda envia `geolocation=()`. O HTML público continua no release anterior; a branch contém as correções e o smoke check pós-publicação.
- [ ] Validar fluxos autenticados de owner/lojista e a jornada completa de pedidos no ambiente de homologação/produção, além dos testes automatizados.
- [ ] Verificar automação de backup e restauração ensaiada do PostgreSQL e MinIO; definir RPO/RTO e retenção documentados.
- [ ] Adotar gestão da stack pelo Portainer somente depois de inventariar o estado atual e confirmar que a stack exclusiva pode ser importada sem recriação destrutiva.

### Integrações e decisões de operação

- [ ] Resolver a divergência da chave SSH antes de autenticar. O Console Web reportou o host ED25519 `SHA256:clM3jUTdk07VMqp23yHEMLn1PUaUeNZjyaE3cGFRkr4` (`root@Automacao360`), enquanto a conexão ao IP configurado `72.61.40.165:22` apresenta `SHA256:QI59bFsz1ODM+ejiuOLM+UYGvqxRyf+GEiWYImLCAXE`. O SSH estrito encerra antes da autenticação; `known_hosts` local contém ED25519 `SHA256:joosSkC58VVOc6VNVVJWfHLlj9AgbHVjg7+i9ZvGtqU`, RSA `SHA256:UfqU/pwV10clrbVh+aQlbmtk2uyDb5TUTI7w1AuwYB8` e ECDSA `SHA256:W39T3YtoxvntLNUohFoHnPnqU+SFbiL3TtXVKlHIpFg`; nada foi alterado. A chave cliente nova `~/.ssh/cardapiodigitalbh_vps_2026` está carregada (`SHA256:LF8qxJSD5JUA4qRhRKFMhzIqETRZMiYmA6bw7RHvoAE`) e o usuário reportou que já a cadastrou no hPanel, mas a autenticação ainda não foi alcançada. Próximo passo sem suporte: conferir no hPanel Overview se o IP atual é `72.61.40.165`; no Console Web, obter `sudo sshd -T | grep "^hostkey "` e conferir fingerprint da chave configurada pelo `sshd`. Só atualizar `known_hosts` após essas informações coincidirem. O `.env` real não foi alterado.
- [ ] Homologar Evolution API real com instância de teste por tenant: QR/conexão, webhook autenticado, outbox, retentativas, idempotência, consentimento e Reset Whats estritamente isolado. Testes atuais usam API simulada.
- [ ] Homologar Mercado Pago por estabelecimento em sandbox e piloto controlado: OAuth/credenciais, PIX e cartão, webhook assinado/idempotente, estados concorrentes, estorno/reembolso, reconciliação e falhas. Não habilitar cobrança real antes da aprovação do piloto.
- [x] Implementado: verificação de e-mail obrigatória antes de qualquer painel; owner e equipe recebem o fluxo de verificação. Validar entrega SMTP real e o convite de um membro no piloto.
- [ ] Definir política e alarmes de retenção/ciclo de vida do MinIO com base em crescimento, custo, recuperação e requisitos de clientes; mídia atual não deve ser apagada automaticamente. A auditoria local não pôde coletar métricas porque o bucket não está configurado neste `.env`; executar o comando somente leitura no container/VPS após confirmar o bucket. `deploy/validate-minio.sh` agora usa objeto de nome aleatório e tenta removê-lo mesmo após falha; sintaxe local passou, execução real pendente.
- [ ] Web Push implementado localmente, aguardando configurar VAPID no ambiente, aplicar migration, testar inscrição/entrega em browsers reais e confirmar uso no celular. Não anunciar entrega real até o piloto.
- [x] Agente local Windows implementado em `printer-agent/`: usa o spooler/driver instalado pelo cliente (incluindo Epson TM-T20), pareamento de uso único, token protegido por DPAPI, polling HTTPS, fila recuperável, deduplicação local, seleção da impressora e reimpressão auditada pelo painel. Pacote self-contained Win-x64 com instalador/atualizador manual passou em build, checksum e simulações `-WhatIf`; compatibilidade depende do driver. Piloto físico permanece pendente.
- [x] Implementada autorização temporária de geofence: owner gera código de seis dígitos por mesa; novo código invalida o anterior; validade de 15 minutos; uso único transacional; hash HMAC armazenado; auditoria de geração/uso; alternativa no pedido QR quando localização está bloqueada ou fora do raio.
- [ ] Homologar geofence em dispositivos reais e coordenadas do estabelecimento, incluindo permissões negadas e baixa precisão.

### Expansão comercial (fora do núcleo de lançamento)

- [ ] Cobrança SaaS e gestão de inadimplência.
- [ ] Marketplace, anúncios, afiliados e vouchers.
- [ ] Validar com o responsável o conteúdo legal, identificação/canal do controlador, política e prazos de retenção, suporte operacional e processo de incidente antes de ampliar a base comercial.

### Ordem de execução recomendada

1. Fechar revisão visual móvel e proteger a alteração/artefatos locais.
2. Confirmar release e saúde da produção; revisar backups e recuperação. Rechecagem somente leitura em 2026-10-29: `/` e `/up` retornaram 200, mas `/favicon.ico` veio vazio, os dois assets oficiais SVG/WebP retornaram 404 e `Permissions-Policy` ainda bloqueia geolocalização; o release correto não está comprovado e a publicação depende da resolução do fingerprint SSH divergente descrito acima.
3. Homologar Evolution e o núcleo de pedidos/pagamentos em ambientes controlados.
4. Homologar entrega de verificação de e-mail e Web Push; definir política e alarmes do MinIO.
5. Instalar e homologar o agente de impressão e a entrega Web Push em dispositivos reais.
6. Só depois avançar cobrança SaaS e demais produtos comerciais.

## Matriz de paridade do legado

| Área | Estado | Evidência/observação |
|---|---|---|
| Catálogo, categorias e ordem manual | Implementado/testado | CRUD existente e campo `position` |
| Ordem por popularidade por categoria | Implementado/testado | histórico de itens vendidos; padrão para novos venues |
| Mesas, QR e comandas | Implementado/testado | URL canônica no subdomínio do estabelecimento |
| Estoque de produto e insumos | Implementado/testado | reserva, consumo e proteção contra venda excedente |
| Horários e fechamento automático | Implementado/testado | inclui intervalo após meia-noite |
| Geofence | Implementado localmente | Haversine no servidor e liberação temporária do owner por código de uso único, expiração, hash e auditoria; aceite em dispositivos reais pendente |
| Retirada, delivery, clientes, CEP, frete e cupons | Implementado/testado | totais recalculados no servidor |
| Evolution e Reset Whats | Implementado/testado localmente | teste contratual usa API simulada; homologação real pendente |
| Variações e adicionais | Implementado/testado | grupos obrigatórios/opcionais, limites e preço no servidor |
| Relatórios | Implementado/testado | período, KPIs, canais, produtos e CSV |
| Banners | Implementado | CRUD, agendamento e renderização pública; teste/validação de produção ainda deve acompanhar publicação |
| Impressão QR | Implementado localmente | impressão pelo navegador; agente Windows x64 agora tem pacote autocontido, instalador/atualizador por usuário, checksum e desinstalador; parsing, build, checksum e simulações `-WhatIf` passaram. Piloto físico com Windows e impressoras térmicas (incluindo Epson TM-T20) ainda pendente; atualizações são manuais |
| Web Push | Implementado localmente | consentimento e envio para novos pedidos; VAPID e entrega real ainda aguardam configuração/piloto |
| Gateway de pagamento online por estabelecimento | Implementado localmente | Mercado Pago OAuth/Checkout Pro por lojista; pedido fica aguardando pagamento e só segue para cozinha após aprovação; credenciais sandbox e homologação ainda pendentes |
| Marketplace, afiliados, anúncios e vouchers | Planejado | não bloqueia o lançamento operacional |
