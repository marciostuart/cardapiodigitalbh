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
- Owner de estabelecimento: dashboard, CRUD do catálogo, mesas e QR Code SVG imprimível por mesa.
- Cardápio público no domínio raiz do tenant (`https://{slug}.cardapiodigitalbh.com.br`) e por QR (`/m/{token}`).
- Pedido público por QR com carrinho; preço e total sempre recalculados no servidor.
- Operação: mapa de mesas, KDS visual, avanço de itens, comandas, pagamentos manuais e liberação de mesa.
- Equipe: owner cria acessos de garçom, cozinha e caixa; cada função possui rotas limitadas ao trabalho correspondente.

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
| 2026-08-20 | `/painel/cardapio` retornou 500 | View fechava `@forelse` com `@endforeach`; corrigida e coberta por teste funcional. |
| 2026-08-20 | HTTPS do subdomínio apresentou certificado não confiável | Aplicação e roteamento respondem 200; falta certificado wildcard por DNS challenge. |

## Pendências críticas de lançamento

1. Habilitar HTTPS wildcard para subdomínios por DNS challenge.
2. Configurar SMTP real, recuperação de senha e verificação de e-mail.
3. Adotar a stack pelo Portainer para controle Total quando o produto estiver estável.
