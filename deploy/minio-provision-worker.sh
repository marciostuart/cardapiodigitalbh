#!/bin/sh
set -eu
mc alias set local http://minio:9000 "$ROOT_USER" "$ROOT_PASSWORD" >/dev/null
mc mb --ignore-existing local/cardapiodigitalbh >/dev/null
mc admin user add local "$ACCESS_KEY" "$SECRET_KEY" >/dev/null
mc admin policy create local cardapiodigitalbh /policy.json >/dev/null
mc admin policy attach local cardapiodigitalbh --user "$ACCESS_KEY" >/dev/null
echo READY
