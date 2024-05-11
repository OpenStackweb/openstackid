#!/bin/bash
set -e
export DOCKER_SCAN_SUGGEST=false

docker compose run --rm app composer install
docker compose run --rm app php artisan doctrine:migrations:migrate --no-interaction
docker compose run --rm app php artisan db:seed --force
docker compose run --rm app php artisan idp:create-super-admin test@test.com 1Qaz2wsx!
docker compose run --rm app yarn install
docker compose run --rm app yarn build
docker compose up -d
docker compose exec app /bin/bash