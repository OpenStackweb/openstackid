FROM mcr.microsoft.com/playwright:v1.61.1-jammy

# Docker CLI only (no daemon) - lets the e2e suite shell out to
# `docker exec idp-app php artisan idp:get-latest-otp <email>` to read a
# real OTP value without adding DB/mail dependencies to the test runner.
# Requires /var/run/docker.sock to be mounted at runtime (docker-compose.yml).
ARG DOCKER_CLI_VERSION=27.3.1
RUN apt-get update \
    && apt-get install -y --no-install-recommends ca-certificates curl \
    && curl -fsSL "https://download.docker.com/linux/static/stable/x86_64/docker-${DOCKER_CLI_VERSION}.tgz" \
        | tar -xz --strip-components=1 -C /usr/local/bin docker/docker \
    && rm -rf /var/lib/apt/lists/*
