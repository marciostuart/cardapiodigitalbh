#!/bin/sh
set -eu

project_dir=/opt/cardapiodigitalbh
minio_service=minio_minio
bucket=cardapiodigitalbh
policy=cardapiodigitalbh
env_lines=$(docker service inspect --format '{{range .Spec.TaskTemplate.ContainerSpec.Env}}{{println .}}{{end}}' "$minio_service")
root_user=$(printf '%s\n' "$env_lines" | sed -n 's/^MINIO_ROOT_USER=//p' | head -n1)
root_password=$(printf '%s\n' "$env_lines" | sed -n 's/^MINIO_ROOT_PASSWORD=//p' | head -n1)
access_key=cdbh_$(openssl rand -hex 10)
secret_key=$(openssl rand -hex 24)
test -n "$root_user" && test -n "$root_password"

docker run --rm --network 360Network --entrypoint /bin/sh \
  -e ROOT_USER="$root_user" -e ROOT_PASSWORD="$root_password" -e ACCESS_KEY="$access_key" -e SECRET_KEY="$secret_key" \
  -v "$project_dir/deploy/minio-cardapiodigitalbh-policy.json:/policy.json:ro" minio/mc -c '
    set -eu
    mc alias set local http://minio:9000 "$ROOT_USER" "$ROOT_PASSWORD" >/dev/null
    mc mb --ignore-existing local/cardapiodigitalbh >/dev/null
    mc admin user add local "$ACCESS_KEY" "$SECRET_KEY" >/dev/null
    mc admin policy create local cardapiodigitalbh /policy.json >/dev/null
    mc admin policy attach local cardapiodigitalbh --user "$ACCESS_KEY" >/dev/null
  '

env_file="$project_dir/.env"
for key in MEDIA_DISK AWS_ACCESS_KEY_ID AWS_SECRET_ACCESS_KEY AWS_DEFAULT_REGION AWS_BUCKET AWS_ENDPOINT AWS_USE_PATH_STYLE_ENDPOINT; do
  sed -i "/^${key}=/d" "$env_file"
done
{
  printf '\nMEDIA_DISK=s3\n'
  printf 'AWS_ACCESS_KEY_ID=%s\n' "$access_key"
  printf 'AWS_SECRET_ACCESS_KEY=%s\n' "$secret_key"
  printf 'AWS_DEFAULT_REGION=us-east-1\n'
  printf 'AWS_BUCKET=%s\n' "$bucket"
  printf 'AWS_ENDPOINT=http://minio:9000\n'
  printf 'AWS_USE_PATH_STYLE_ENDPOINT=true\n'
} >> "$env_file"
echo 'MinIO configurado para o bucket exclusivo cardapiodigitalbh.'
