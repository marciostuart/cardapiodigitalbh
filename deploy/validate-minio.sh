#!/bin/sh
set -eu
app=$(docker ps --filter name=cardapiodigitalbh_app --format '{{.ID}}' | head -n1)
test -n "$app"
docker exec "$app" php artisan tinker --execute='Illuminate\Support\Facades\Storage::disk("s3")->put("health/laravel-check.txt", "ok"); dump(Illuminate\Support\Facades\Storage::disk("s3")->get("health/laravel-check.txt")); Illuminate\Support\Facades\Storage::disk("s3")->delete("health/laravel-check.txt");'
if docker service ls | grep -q cdbh_minio_setup; then echo 'Serviço temporário não removido.'; exit 1; fi
