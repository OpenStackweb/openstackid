Run Local Dev Server
====================

1. Create [.env](.env) file with following properties

```dotenv
GITHUB_OAUTH_TOKEN="<GITHUB TOKEN FROM YOUR GITHUB ACCOUNT>"

APP_ENV=local
APP_DEBUG=true
APP_KEY=<YOUR LV APP KEY>
DEV_EMAIL_TO=smarcet@gmail.com
APP_URL=http://localhost
DB_CONNECTION=mysql
DB_HOST=db
DB_PORT=3306
DB_DATABASE=idp_local
DB_USERNAME=idp_user
DB_PASSWORD=1qaz2wsx!
REDIS_HOST=redis
REDIS_PORT=6379
REDIS_DB=0
REDIS_PASSWORD=1qaz2wsx!
REDIS_DATABASES=16
SSL_ENABLED=false
```
2.( optional ) Drop here  [docker-compose/mysql/model](docker-compose/mysql/model) the database dump *.sql file
3.Install docker and docker compose see
[https://www.digitalocean.com/community/tutorials/how-to-install-and-use-docker-compose-on-ubuntu-22-04](https://www.digitalocean.com/community/tutorials/how-to-install-and-use-docker-compose-on-ubuntu-22-04) and [https://www.digitalocean.com/community/tutorials/how-to-install-and-use-docker-on-ubuntu-22-04](https://www.digitalocean.com/community/tutorials/how-to-install-and-use-docker-on-ubuntu-22-04)
4.Run script ./start_local_server.sh (http://localhost:8001/)

Frontend Development
====================

openstackid is a Laravel app — blade templates reference compiled JS bundles in `public/assets/`, not source files. The source files in `resources/js/` must be compiled before changes take effect in the browser.

**One-time setup:** The compiled assets in `public/assets/` are owned by root if they were originally built inside Docker. Fix ownership before running any build commands:

```bash
sudo chown -R $(whoami) public/assets
```

**Build and watch** (recompiles automatically on save, Ctrl+C to stop):

```bash
npm run build-dev
```

To do a one-shot build without watch mode:

```bash
./node_modules/.bin/webpack --config webpack.prod.js
```

**Captcha:** The reset password and other pages require a Cloudflare Turnstile site key. Add test keys to `.env` to use the captcha locally without a real Cloudflare account:

```dotenv
TURNSTILE_SITE_KEY=1x00000000000000000000AA
TURNSTILE_SECRET_KEY=1x0000000000000000000000000000000AA
```

**Email:** Outbound email is logged to a date-stamped file in `storage/logs/` rather than sent. However, emails are queued via Redis by default, so they are not processed until a queue worker runs. After submitting the forgot password form, process the queue to trigger the log entry:

```bash
docker exec idp-app php artisan queue:work --stop-when-empty
```

This processes all pending jobs and exits when the queue is empty. Use `--once` if you only want to process a single job.

To retrieve a password reset link after processing the queue:

```bash
grep "password/reset" storage/logs/laravel-$(date +%Y-%m-%d).log | tail -1
```

After editing `.env`, clear the config cache:

```bash
docker exec idp-app php artisan config:clear
```

Redump the database
===================

````bash
    mysql -u root -h 127.0.0.1 -P 30780 --password=<DB_PASSWORD> < mydump.sql
````

Useful Commands
===============

check containers health status

````bash
docker inspect --format "{{json .State.Health }}" www-openstack-model-db-local | jq '.
````