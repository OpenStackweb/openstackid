# OpenstackId Idp

## Documentation

* https://wiki.openstack.org/wiki/OpenStackID
* https://docs.openstack.org/infra/openstackid/

## Prerequisites

    * LAMP/LEMP environment
    * Redis
    * PHP >= 7.0
    * composer (https://getcomposer.org/)

## Install

run following commands on root folder
   * curl -s https://getcomposer.org/installer | php
   * php composer.phar install --prefer-dist
   * php composer.phar dump-autoload --optimize
   * php artisan migrate --env=YOUR_ENVIRONMENT
   * php artisan db:seed --env=YOUR_ENVIRONMENT
   * phpunit --bootstrap vendor/autoload.php
   * give proper rights to app/storage folder (775 and proper users)
   * vendor/bin/behat --config /home/smarcet/git/openstackid/behat.yml


## Permissions

Laravel may require some permissions to be configured: folders within storage and vendor require write access by the
web server.

chmod 777 vendor/ezyang/htmlpurifier/library/HTMLPurifier/DefinitionCache/Serializer


## Permissions

Laravel may require some permissions to be configured: folders within storage and vendor require write access by the web server.

## validate schema

php artisan doctrine:schema:validate

## create schema

php artisan doctrine:schema:create --sql --em=model > model.sql

## Doctrine Migrations

## create new migrations

php artisan doctrine:migrations:generate --connection=model --create=<table-name>

## check status
php artisan doctrine:migrations:status --connection=model

## run
php artisan doctrine:migrations:migrate --connection=model

# start queue worker

php artisan queue:work

# create super user

php artisan idp:create-super-admin {email} {password}

# seed db

php artisan db:seed --force --env={env}

# nvm

## install it

curl -o- https://raw.githubusercontent.com/nvm-sh/nvm/v0.37.2/install.sh | bash

nvm use

# Tests

## Backend (PHPUnit)

```bash
php artisan view:clear
php artisan cache:clear
./vendor/bin/phpunit
```

## Frontend — Unit/Component (Jest)

Run from inside the `idp-app` container:

```bash
yarn test:unit        # watch mode
yarn test:unit:ci     # single run with coverage
```

## Frontend — E2E (Playwright)

Run from the **host** (outside any container). The full stack must be running (`./start_local_server.sh`).

```bash
# Run all E2E tests
docker compose --profile e2e run --rm playwright npx playwright test

# Run a specific file
docker compose --profile e2e run --rm playwright npx playwright test tests/e2e/tests/auth/login.spec.ts
```

> E2E tests cannot be run from inside the `idp-app` container — it has no browser.
> The `playwright` service (`mcr.microsoft.com/playwright:v1.61.1-jammy`) includes Chromium and all required system dependencies.

### Viewing the HTML report

The report is written to `tests/e2e/report/` on the host. Serve it from the host (not from inside any container) so the browser can reach it:

```bash
nvm use 22.2.0
yarn test:e2e:report
# Open http://localhost:9323
```

# install docker compose

https://www.digitalocean.com/community/tutorials/how-to-install-and-use-docker-compose-on-ubuntu-22-04

# remote docker debugging

1. Config the PHPStorm CLI interpreter ( Docker Image )
2. Config the Server setting in PHPStorm ( 0.0.0.0 / 80 and map root to /var/www)
3. Setup debug configuration
see
https://www.tsukie.com/en/technologies/debug-laravel-web-application-in-docker-with-xdebug-and-phpstorm
and
https://medium.com/the-sensiolabs-tech-blog/how-to-use-xdebug-in-docker-phpstorm-76d998ef2534

# Docker Compose

https://gist.github.com/mkfares/41c9609fcde8d9f665210034e99d4bd9