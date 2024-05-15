FROM php:8.2-fpm

ARG DEBIAN_FRONTEND=noninteractive
ARG NVM_VERSION="v0.39.7"
ARG GITHUB_OAUTH_TOKEN
ARG XDEBUG_VERSION="xdebug-3.3.2"

ENV NVM_VERSION=$NVM_VERSION
ENV NODE_VERSION="18.20.2"
ENV NVM_DIR /usr/local/nvm
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

# nvm

RUN mkdir $NVM_DIR  \
    && curl https://raw.githubusercontent.com/creationix/nvm/$NVM_VERSION/install.sh | bash \
    && . $NVM_DIR/nvm.sh \
    && nvm install $NODE_VERSION \
    && nvm alias default $NODE_VERSION \
    && nvm use default

ENV NODE_PATH $NVM_DIR/v$NODE_VERSION/lib/node_modules
ENV PATH      $NVM_DIR/v$NODE_VERSION/bin:$PATH

# yarn
RUN curl -sS https://dl.yarnpkg.com/debian/pubkey.gpg | apt-key add -
RUN echo "deb https://dl.yarnpkg.com/debian/ stable main" | tee /etc/apt/sources.list.d/yarn.list
RUN  apt update && apt install -y yarn

WORKDIR /var/www
COPY . /var/www
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

RUN composer config -g github-oauth.github.com $GITHUB_OAUTH_TOKEN
RUN chmod 777 -R storage