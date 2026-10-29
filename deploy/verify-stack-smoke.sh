#!/bin/sh
set -eu

stack=cardapiodigitalbh
base_url=${CARDAPIO_PUBLIC_URL:-https://cardapiodigitalbh.com.br}
base_url=${base_url%/}
services='app web worker payments-worker'

fail() {
    echo "ERRO: $*" >&2
    exit 1
}

service_rows=$(docker service ls \
    --filter "label=com.docker.stack.namespace=$stack" \
    --format '{{.Name}} {{.Replicas}}') || fail 'nao foi possivel listar os servicos Swarm.'

for name in $services; do
    service="${stack}_${name}"
    replicas=$(printf '%s\n' "$service_rows" | awk -v target="$service" '$1 == target { print $2 }')
    [ "$replicas" = '1/1' ] || fail "$service esta com replicas '${replicas:-ausentes}', esperado 1/1."
    printf 'OK %s replicas=%s\n' "$service" "$replicas"
done

for path in / /up /favicon.ico /images/cardapio-digital-bh-logo.svg /images/cardapio-digital-bh-favicon.webp; do
    cache_bust=
    case "$path" in
        /favicon.ico|/images/*) cache_bust="?smoke=$(date +%s)-$$" ;;
    esac
    bytes=$(curl -fsS --max-time 15 -o /dev/null -w '%{size_download}' "$base_url$path$cache_bust") \
        || fail "HTTP falhou em $base_url$path."
    case "$bytes" in
        ''|*[!0-9]*) fail "resposta invalida ao verificar $base_url$path." ;;
    esac
    [ "$bytes" -gt 0 ] || fail "$base_url$path respondeu HTTP sem conteudo."
    printf 'OK HTTP %s bytes=%s\n' "$path" "$bytes"
done

headers=$(curl -fsS --max-time 15 -D - -o /dev/null "$base_url/") || fail 'nao foi possivel ler os cabecalhos HTTP.'
printf '%s\n' "$headers" | tr -d '\r' | grep -Eiq '^permissions-policy:.*geolocation[[:space:]]*=[[:space:]]*\(self\)' \
    || fail 'Permissions-Policy nao permite geolocalizacao na propria origem.'

echo 'Smoke check concluido. Fluxos autenticados e pilotos fisicos ainda precisam de validacao propria.'
