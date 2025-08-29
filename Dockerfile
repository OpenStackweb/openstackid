FROM php:8.3-fpm

ARG DEBIAN_FRONTEND=noninteractive
ARG NVM_VERSION="v0.40.3"
ENV NODE_VERSION="18.20.4"
ARG YARN_VERSION="1.22.22"
ARG GITHUB_OAUTH_TOKEN
ARG XDEBUG_VERSION="xdebug-3.3.2"

ENV NVM_VERSION=$NVM_VERSION
ENV NVM_DIR=/root/.nvm
ENV COMPOSER_ALLOW_SUPERUSER=1
ENV GITHUB_OAUTH_TOKEN=$GITHUB_OAUTH_TOKEN
ENV PHP_DIR /usr/local/etc/php

# base packages
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    redis-tools \
    nano \
    python3 \
    make \
    g++\
    gpg \
    gettext


RUN apt clean && rm -rf /var/lib/apt/lists/*

RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath sockets gettext
# XDEBUG
RUN yes | pecl install ${XDEBUG_VERSION}
COPY docker-compose/php/docker-php-ext-xdebug.ini $PHP_DIR/conf.d/docker-php-ext-xdebug.ini

RUN mv "$PHP_INI_DIR/php.ini-development" "$PHP_INI_DIR/php.ini"
RUN echo 'memory_limit = 512M' >> $PHP_INI_DIR/php.ini;

# nvm + node + yarn via corepack
ENV NVM_DIR=/root/.nvm
RUN curl -o- https://raw.githubusercontent.com/nvm-sh/nvm/$NVM_VERSION/install.sh | bash
# Install Node, enable Corepack (Yarn)
RUN bash -lc "source $NVM_DIR/nvm.sh && nvm install $NODE_VERSION && corepack enable && corepack prepare yarn@$YARN_VERSION --activate"
RUN apt clean && rm -rf /var/lib/apt/lists/*

# Set up our PATH correctly so we don't have to long-reference npm, node, &c.
ENV NODE_PATH=$NVM_DIR/versions/node/v$NODE_VERSION/lib/node_modules
ENV PATH=$NVM_DIR/versions/node/v$NODE_VERSION/bin:$PATH

WORKDIR /var/www
COPY . /var/www
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

RUN composer config -g github-oauth.github.com $GITHUB_OAUTH_TOKEN
RUN chmod 777 -R storage
RUN git config --global --add safe.directory /var/www
