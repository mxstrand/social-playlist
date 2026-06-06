#!/bin/sh
# Production entrypoint: migrate + seed, then serve.
# (Prod cache + bundle assets are warmed at BUILD time — see Dockerfile.prod.)
# Idempotent — safe to run on every boot/redeploy.
set -e

# The Mercure hub listens on $PORT inside this container, so PHP must publish to
# 127.0.0.1:$PORT. PORT is dynamic on Railway — derive MERCURE_URL if not provided
# (you only need to set MERCURE_PUBLIC_URL, the browser-facing https URL).
export MERCURE_URL="${MERCURE_URL:-http://127.0.0.1:${PORT:-8000}/.well-known/mercure}"
echo "[entrypoint] MERCURE_URL=${MERCURE_URL}"

echo "[entrypoint] running database migrations..."
php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration

echo "[entrypoint] seeding starter catalog (idempotent)..."
php bin/console app:seed

echo "[entrypoint] starting FrankenPHP on :${PORT:-8000}..."
exec frankenphp run --config /etc/frankenphp/Caddyfile
