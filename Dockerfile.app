FROM php:8.4-fpm-alpine AS php-base
WORKDIR /var/www/html
COPY docker/php/uploads.ini /usr/local/etc/php/conf.d/99-cardapiodigitalbh-uploads.ini
RUN apk add --no-cache $PHPIZE_DEPS postgresql-dev libzip-dev oniguruma-dev libxml2-dev libpng-dev libjpeg-turbo-dev freetype-dev libwebp-dev && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp && docker-php-ext-install pdo_pgsql mbstring zip opcache simplexml gd exif && pecl install redis && docker-php-ext-enable redis && apk del $PHPIZE_DEPS
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

FROM php-base AS vendor
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --prefer-dist --no-scripts

FROM node:22-alpine AS frontend
WORKDIR /build
COPY package.json package-lock.json ./
RUN npm ci
COPY resources ./resources
COPY public ./public
COPY vite.config.js postcss.config.js tailwind.config.js ./
COPY --from=vendor /var/www/html/vendor/laravel/framework/src/Illuminate/Pagination/resources/views ./vendor/laravel/framework/src/Illuminate/Pagination/resources/views
RUN npm run build

FROM php-base AS app
COPY composer.json composer.lock ./
COPY --from=vendor /var/www/html/vendor ./vendor
COPY . .
COPY --from=frontend /build/public/build ./public/build
RUN mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views bootstrap/cache \
    && composer dump-autoload --no-dev --optimize \
    && chown -R www-data:www-data storage bootstrap/cache
CMD ["php-fpm", "-F"]
