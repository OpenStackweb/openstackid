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