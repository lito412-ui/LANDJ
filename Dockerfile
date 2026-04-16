FROM php:8.3-fpm-alpine

RUN apk add --no-cache mariadb-connector-c \
    && apk add --no-cache --virtual .build-deps build-base mariadb-dev \
    && docker-php-ext-install pdo_mysql \
    && apk del .build-deps