# OpenstackId Idp

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
   * php artisan vendor:publish --provider="Greggilbert\Recaptcha\RecaptchaServiceProvider"
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

## create SS schema

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