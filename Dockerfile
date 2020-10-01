FROM php:7.4-cli

RUN apt-get update && \
    apt-get install -y \
        libpq-dev && \
    docker-php-ext-install pdo pdo_pgsql


WORKDIR /usr/src/app