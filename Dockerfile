FROM php:7.4-cli AS base

RUN apt-get update && \
    apt-get install -y \
        libpq-dev \
        zip \
        unzip && \
    docker-php-ext-install pdo pdo_pgsql sockets

WORKDIR /src

FROM base AS builder

COPY --from=composer /usr/bin/composer /usr/bin/composer
COPY composer.json composer.lock ./
COPY src  /src/src/

RUN composer install


FROM base AS test

COPY --from=builder /src /src
COPY tests /src/tests/
COPY phpunit.xml /src/phpunit.xml
RUN mkdir -p /src/var/logs/test

