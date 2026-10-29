#!/bin/sh
set -eu

project_dir=/opt/cardapiodigitalbh
env_file="$project_dir/.env"
service=cardapiodigitalbh_app
test -f "$env_file"
image=$(docker service inspect "$service" --format '{{.Spec.TaskTemplate.ContainerSpec.Image}}')
test -n "$image"

# The private VAPID key is written directly into the protected host .env file.
# It is never printed, passed as a command argument, or written to a project volume.
docker run --rm --network none --user 0:0 \
  --mount "type=bind,src=$env_file,dst=/tmp/cardapiodigitalbh.env" \
  --entrypoint php "$image" /var/www/html/artisan webpush:keys --env-file=/tmp/cardapiodigitalbh.env

echo 'VAPID keys were written to the project .env. Redeploy only the cardapiodigitalbh stack to apply them.'
