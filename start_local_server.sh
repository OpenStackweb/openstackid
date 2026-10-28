#!/bin/bash
set -e
export DOCKER_SCAN_SUGGEST=false

# Install PHP deps without running post-autoload scripts (package:discover
# boots Laravel which triggers the OTEL exporter flush — hangs if the
# collector isn't up yet).
docker compose run --rm app composer install --no-scripts

# JS deps and build don't involve artisan, safe to run before the full stack.
docker compose run --rm app yarn install
docker compose run --rm app yarn build

# Bring up the full stack so the OTEL collector is reachable before any
# artisan command runs.
docker compose up -d

echo "Waiting for app container to be ready..."
until docker compose exec app true 2>/dev/null; do sleep 1; done

# Now run artisan commands with every service available.
docker compose exec app php artisan package:discover --ansi
docker compose exec app php artisan doctrine:migrations:migrate --no-interaction
docker compose exec app php artisan db:seed --force
docker compose exec app php artisan idp:create-super-admin test@test.com 1Qaz2wsx!
docker compose exec app php artisan idp:create-raw-user e2e@test.com 1Qaz2wsx!

# Install Playwright Chromium into the named volume (skipped automatically if
# already cached from a previous run).
docker compose --profile e2e run --rm playwright npx playwright install chromium

docker compose exec app /bin/bash
