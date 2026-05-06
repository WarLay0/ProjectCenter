FROM php:8.4-fpm-alpine

# Dependances + extensions PHP (Postgres + intl + zip)
RUN apk add --no-cache git icu-dev libzip-dev postgresql-dev \
 && docker-php-ext-install intl opcache pdo_pgsql zip

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/backend

COPY . .
RUN composer install --no-scripts --no-interaction --prefer-dist

EXPOSE 9000

CMD ["php-fpm"]
