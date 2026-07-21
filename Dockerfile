FROM php:8.3-fpm-alpine

# Dependencias del sistema + extensiones PHP
RUN apk add --no-cache mariadb-connector-c curl \
    && apk add --no-cache --virtual .build-deps build-base mariadb-dev \
    && docker-php-ext-install pdo_mysql \
    && apk del .build-deps

# Composer disponible en la imagen
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
