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
- Owner de plataforma (`platform_owner`): `/admin`, lista estabelecimentos, altera plano e pode impersonar o owner do cliente.
- Owner de estabelecimento: dashboard, categorias, produtos, mesas e token QR por mesa.
- Cardápio público por QR: `/m/{token}`.
- Operação inicial: pedidos, comandas, pagamentos manuais e KDS possuem base de domínio/API.

## Padrões obrigatórios

- Toda consulta/mutação de cliente deve ser isolada por `tenant_id`/`venue_id`.
- Rotas internas exigem autenticação; rotas públicas não expõem IDs sensíveis.
- Nunca tocar em stacks, banco, rede ou volumes de outros projetos da VPS.
- Todo deploy deve: testes locais, build da imagem, migration quando aplicável, atualização apenas da stack e smoke test HTTP.
- Segredos ficam somente em `/opt/cardapiodigitalbh/.env`; nunca Git/logs/documentação.
- Atualizar este arquivo em mudanças arquiteturais, incidentes e decisões relevantes.

## Produção e deploy

- Diretório VPS: `/opt/cardapiodigitalbh`.
- Rede compartilhada somente para conectividade: `360Network`.
- Banco exclusivo: `cardapiodigitalbh`; usuário exclusivo criado na VPS.
- Traefik usa `letsencryptresolver` para o domínio principal.
- Para subdomínios HTTPS em escala, é necessário certificado wildcard via DNS challenge; não alterar Traefik global sem autorização explícita.
- Caddy serve assets compilados. A causa de CSS ausente foi volume do host mascarando `public/build`; solução permanente: imagem `web` no Dockerfile copia assets da imagem `app`.

## Incidentes e correções

| Data | Problema | Correção |
|---|---|---|
| 2026-08-20 | Rotas removidas pelo scaffold de auth | Rotas operacionais restauradas e cobertas por testes. |
| 2026-08-20 | CSS/JS ausentes no Caddy | Assets sincronizados; Dockerfile evoluído para imagem web própria. |
| 2026-08-20 | `/admin` retornou 500 | Controller Admin não havia sido copiado para a VPS; diretório e imagem corrigidos. |
| 2026-08-20 | `/menu` no domínio principal retornou 404 | Menu depende de tenant; prévia deve usar slug/subdomínio ou token QR. |

## Pendências críticas de lançamento

1. Corrigir página de cardápio interno (`/painel/cardapio`) e testes de fluxo autenticado.
2. Finalizar link de prévia com slug e habilitar HTTPS wildcard para subdomínios.
3. Exibir QR Code imprimível por mesa, não apenas URL.
4. Completar pedido público por QR e atualização de status para cozinha/caixa.
5. Configurar SMTP real, recuperação de senha e verificação de e-mail.
6. Adotar a stack pelo Portainer para controle Total quando o produto estiver estável.
