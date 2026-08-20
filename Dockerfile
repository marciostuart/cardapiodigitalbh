FROM php:8.4-cli-alpine
WORKDIR /var/www/html
RUN apk add --no-cache postgresql-dev libzip-dev && docker-php-ext-install pdo_pgsql zip
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
COPY . .
RUN composer install --no-dev --optimize-autoloader
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8080"]
