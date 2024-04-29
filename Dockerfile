FROM php:8.2-fpm
ARG DEBIAN_FRONTEND=noninteractive
ARG NVM_VERSION="v0.39.7"
ENV NVM_VERSION=$NVM_VERSION
# base packages
ENV NODE_VERSION="16.17.1"
ENV NVM_DIR=/root/.nvm

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
    gpg \
    gettext

RUN apt clean && rm -rf /var/lib/apt/lists/*

RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath sockets gettext

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# install nvm and yarn

RUN echo "Package: node* \nPin: release *\nPin-Priority: -1" > /etc/apt/preferences.d/no-debian-nodejs && \
    mkdir -p /etc/apt/keyrings && \
    curl -fsSL https://deb.nodesource.com/gpgkey/nodesource-repo.gpg.key | gpg --dearmor -o /etc/apt/keyrings/nodesource.gpg && \
    NODE_MAJOR=18 && \
    echo "deb [signed-by=/etc/apt/keyrings/nodesource.gpg] https://deb.nodesource.com/node_$NODE_MAJOR.x nodistro main" | tee /etc/apt/sources.list.d/nodesource.list && \
    apt-get update && \
    apt-get install nodejs -y
# nvm
RUN curl -o- https://raw.githubusercontent.com/nvm-sh/nvm/$NVM_VERSION/install.sh | bash
RUN  \. ~/.nvm/nvm.sh && nvm install $NODE_VERSION

# yarn
RUN curl -sS https://dl.yarnpkg.com/debian/pubkey.gpg | apt-key add -
RUN echo "deb https://dl.yarnpkg.com/debian/ stable main" | tee /etc/apt/sources.list.d/yarn.list
RUN  apt update && apt install -y yarn

WORKDIR /var/www
COPY .env.local .env

RUN composer config -g github-oauth.github.com $GITHUB_OAUTH_TOKEN
