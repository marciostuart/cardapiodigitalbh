#!/bin/sh
set -eu

project_dir=/opt/cardapiodigitalbh
minio_service=minio_minio
bucket=cardapiodigitalbh
policy=cardapiodigitalbh
minio_container=$(docker ps --filter name=minio_minio --format '{{.ID}}' | head -n1)
root_user=$(docker exec "$minio_container" printenv MINIO_ROOT_USER)
root_password=$(docker exec "$minio_container" printenv MINIO_ROOT_PASSWORD)
access_key=cdbh_$(openssl rand -hex 10)
secret_key=$(openssl rand -hex 24)
test -n "$root_user" && test -n "$root_password"

docker service rm cdbh_minio_setup >/dev/null 2>&1 || true
docker service create --detach --name cdbh_minio_setup --network 360Network --restart-condition none --entrypoint /bin/sh \
  --mount type=bind,src="$project_dir/deploy/minio-cardapiodigitalbh-policy.json",dst=/policy.json,readonly \
  --mount type=bind,src="$project_dir/deploy/minio-provision-worker.sh",dst=/worker.sh,readonly \
  -e ROOT_USER="$root_user" -e ROOT_PASSWORD="$root_password" -e ACCESS_KEY="$access_key" -e SECRET_KEY="$secret_key" minio/mc /worker.sh >/dev/null
ready=0
i=0
while [ "$i" -lt 30 ]; do
  if docker service logs cdbh_minio_setup 2>&1 | grep -q READY; then ready=1; break; fi
  i=$((i+1)); sleep 2
done
if [ "$ready" -ne 1 ]; then docker service ps cdbh_minio_setup --no-trunc; docker service logs cdbh_minio_setup; exit 1; fi
docker service rm cdbh_minio_setup >/dev/null

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
