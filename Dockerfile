FROM composer:2 AS vendor
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --prefer-dist --no-interaction
COPY . .
RUN composer dump-autoload --optimize --no-dev

FROM node:22-alpine AS assets
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci
COPY resources resources
COPY vite.config.js ./
COPY public public
RUN npm run build

FROM php:8.4-fpm-alpine AS app
RUN apk add --no-cache icu-dev libzip-dev \
    && docker-php-ext-install intl opcache pcntl pdo_mysql zip
WORKDIR /var/www/html
COPY --from=vendor /app .
COPY --from=assets /app/public/build public/build
RUN chown -R www-data:www-data storage bootstrap/cache
USER www-data
CMD ["php-fpm", "-F"]

FROM nginx:1.27-alpine AS web
COPY docker/nginx.conf /etc/nginx/conf.d/default.conf
COPY --from=assets /app/public /var/www/html/public
