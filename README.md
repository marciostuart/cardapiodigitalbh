# Cardapio Digital BH

Plataforma independente, multi-tenant, para cardapios digitais, atendimento de salao, comandas, caixa e cozinha. O legado Escolha TOP nao e utilizado nem alterado por este projeto.

## Ambiente local

Copie `.env.example` para `.env`, execute `php artisan key:generate`, `php artisan migrate` e `npm run dev`.

## Publicacao no Portainer

1. Crie o banco PostgreSQL `cardapiodigitalbh` e o bucket MinIO `cardapiodigitalbh`.
2. No repositorio clonado pelo Portainer, copie `.env.production.example` para `.env` e preencha os valores sem inclui-los no Git.
3. Crie a rede externa `traefik` se ela ainda nao existir e confirme que os nomes `postgres`, `redis` e `minio` sao acessiveis pela stack.
4. Publique `docker-compose.yml` pelo recurso Git do Portainer.
5. Execute uma unica vez no container `app`: `php artisan migrate --force` e `php artisan storage:link`.
6. Verifique `https://cardapiodigitalbh.com.br/up` e depois um subdominio de estabelecimento.

O Traefik recebe o dominio raiz e os subdominios. Caddy atende HTTP internamente e o PHP-FPM executa Laravel. O worker processa filas no Redis.

Para a Evolution API, configure somente no ambiente de produção: `EVOLUTION_API_URL`, `EVOLUTION_API_KEY` e `EVOLUTION_WEBHOOK_SECRET`. O webhook deve apontar para `https://cardapiodigitalbh.com.br/webhooks/evolution` e enviar o segredo no cabeçalho `X-Webhook-Secret`.

## Validacao

```
php artisan test
npm run build
```
