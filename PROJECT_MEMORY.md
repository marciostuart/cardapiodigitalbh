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

## Estado consolidado em 2026-08-20

- Repositório: `github.com/marciostuart/cardapiodigitalbh`, branch `main`. Todo código de produto deve ser versionado; não incluir `.env`, chaves, tokens, sessões ou URLs assinadas.
- Produção: VPS em `/opt/cardapiodigitalbh`, stack Swarm isolada `cardapiodigitalbh`, serviços `app`, `web` (Caddy) e `worker`. A rede `360Network` é somente a conexão compartilhada com o proxy; nunca alterar stacks, rede, volumes, banco ou serviços de outros projetos.
- Publicação comprovada mais recente: tag/imagens `6719fe9`, os três serviços em `1/1`. O processo é: testes locais, `git` commit/push, gerar arquivo do commit, enviar para `releases/`, extrair somente em `/opt/cardapiodigitalbh`, construir as duas imagens desta tag, atualizar somente a stack e validar HTTP/serviços.
- Arquitetura: Laravel, PostgreSQL exclusivo, Redis, fila em worker, MinIO/S3 privado por tenant e Caddy. As mídias públicas são entregues por `/media` sem sessão, com isolamento de subdomínio/tenant, cache público de um ano e cache complementar no PWA. Não substituir MinIO por armazenamento local sem antes projetar persistência, backup, escalabilidade e rollback.
- Domínios: o institucional é `cardapiodigitalbh.com.br`; cada cardápio usa exclusivamente `https://{slug}.cardapiodigitalbh.com.br`. Alterar slug exige aviso de quebra de links/QR Codes antigos. O estabelecimento demonstrativo autorizado usa `paulinlanches`.
- Identidade da vitrine: cores e logo do estabelecimento são fonte da interface pública, favicon e PWA. Cabeçalho limpo com faixa superior na cor primária, saudação à esquerda, estado aberto à direita, logo/nome e busca; sem pedido mínimo, compartilhamento ou sacola no cabeçalho. Categorias ficam em trilho horizontal com “Todas”; títulos são sobrepostos e centralizados nas fotos com sombra. O botão fixo do carrinho é o acesso à sacola.
- Produtos: imagem destaque mais galeria de até quatro imagens adicionais; uploads convertidos para WebP, máximo 1.400 px e compressão adaptativa 68% a 55%, meta de cerca de 140 KB. Produtos com grupos obrigatórios abrem personalização; ao atingir o máximo de escolhas, a opção mais antiga é desmarcada automaticamente. Ordenação padrão é por popularidade dentro da categoria, com alternativa manual.
- Pedido público: retirada, mesa e delivery; nome/telefone opcionais fora de comanda de mesa, WhatsApp obrigatório apenas com consentimento. CEP usa ViaCEP. Após envio, modal oferece acompanhar ou continuar no cardápio; acompanhamento mantém identidade visual do tenant, recarrega a cada 20 segundos e permite voltar a comprar.
- PWA: manifesto e service worker são específicos do tenant. O app guarda home e mídias acessadas; uma nova versão apresenta “Atualizar”. A verificação periódica é a cada cinco minutos para equilibrar atualização e consumo de recursos.
- Administração: `/owner` é o painel executivo da plataforma; `/admin` é a gestão de tenants/planos/impersonação e suporte. O painel do lojista usa `/painel/*`. O Owner pode redefinir somente a instância WhatsApp do tenant selecionado. GTM global é configurado pelo Owner; o lojista usa campos validados para Analytics e Pixel da Meta, nunca scripts arbitrários.
- Catálogo de demonstração: importação autorizada de Paulin Lanches concluída com 83 produtos, 8 categorias, 83 imagens e 173 opções. Tratar como dados de demonstração do tenant existente; manter consistência e não importar novamente sem necessidade.
- Qualidade: no último conjunto validado, `php artisan test` passou com 80 testes e 268 asserções. Sempre repetir testes, compilação das views e smoke test dos endereços afetados antes de afirmar publicação.

## Pendências pós-lançamento

1. Adotar a stack pelo Portainer para controle Total quando o produto estiver estável.
2. Decidir quando tornar a verificação de e-mail obrigatória; o template e a infraestrutura de envio já estão preparados.
3. Monitorar o crescimento e definir política de retenção/ciclo de vida do bucket MinIO.
4. Completar banners, Web Push e agente de impressão.
5. Homologar Mercado Pago por estabelecimento somente após estabilização do núcleo operacional.
6. Em etapa posterior, implementar cobrança SaaS, marketplace, anúncios, afiliados e vouchers.

## Matriz de paridade do legado

| Área | Estado | Evidência/observação |
|---|---|---|
| Catálogo, categorias e ordem manual | Implementado/testado | CRUD existente e campo `position` |
| Ordem por popularidade por categoria | Implementado/testado | histórico de itens vendidos; padrão para novos venues |
| Mesas, QR e comandas | Implementado/testado | URL canônica no subdomínio do estabelecimento |
| Estoque de produto e insumos | Implementado/testado | reserva, consumo e proteção contra venda excedente |
| Horários e fechamento automático | Implementado/testado | inclui intervalo após meia-noite |
| Geofence | Implementado/testado | Haversine no servidor; liberação temporária ainda pendente |
| Retirada, delivery, clientes, CEP, frete e cupons | Implementado/testado | totais recalculados no servidor |
| Evolution e Reset Whats | Implementado/testado localmente | teste contratual usa API simulada; homologação real pendente |
| Variações e adicionais | Implementado/testado | grupos obrigatórios/opcionais, limites e preço no servidor |
| Relatórios | Implementado/testado | período, KPIs, canais, produtos e CSV |
| Banners, impressão e Web Push | Planejado | próximo lote operacional |
| Mercado Pago por estabelecimento | Planejado | liberar por feature flag após piloto |
| Marketplace, afiliados, anúncios e vouchers | Planejado | não bloqueia o lançamento operacional |
