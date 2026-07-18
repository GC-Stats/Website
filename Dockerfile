# ============================================================
# Stage 1 — Building assets
# ============================================================
FROM node:24-alpine AS frontend

WORKDIR /app

COPY package*.json ./
RUN npm ci

COPY resources/ resources/
COPY vite.config.* ./
COPY tailwind.config.* ./
COPY postcss.config.* ./

RUN npm run build

# ============================================================
# Stage 2 — Building Laravel
# ============================================================
FROM php:8.4-fpm AS production

RUN apt-get update && apt-get install -y \
    apache2 \
    git \
    curl \
    libonig-dev \
    libxml2-dev \
    libpng-dev \
    libjpeg-dev \
    libwebp-dev \
    zip \
    unzip \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd \
    && docker-php-ext-configure gd --with-jpeg --with-webp \
    && docker-php-ext-install gd \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

RUN a2dismod php* mpm_prefork mpm_worker 2>/dev/null || true \
    && a2enmod mpm_event proxy_fcgi \
    && mkdir -p /var/run/apache2 /var/log/apache2 /var/lock/apache2 \
    && chown -R www-data:www-data /var/run/apache2 /var/log/apache2 /var/lock/apache2

COPY apache-static.conf /etc/apache2/sites-available/000-default.conf

RUN echo '[www]' > /usr/local/etc/php-fpm.d/zz-listen.conf \
    && echo 'listen = 0.0.0.0:9000' >> /usr/local/etc/php-fpm.d/zz-listen.conf

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

ARG RELEASE_VERSION=dev
ENV APP_VERSION=$RELEASE_VERSION

COPY --chown=www-data:www-data composer.json composer.lock ./

RUN composer install --optimize-autoloader --no-dev --no-scripts --no-interaction --prefer-source

COPY --chown=www-data:www-data . .

RUN rm -rf /var/www/html/public/build

COPY --chown=www-data:www-data --from=frontend /app/public/build ./public/build

# the flag-icons country list is necessary for Countries.php.
COPY --chown=www-data:www-data --from=frontend /app/node_modules/flag-icons/country.json ./resources/data/countries.json

RUN mkdir -p storage/framework/{sessions,views,cache} \
    storage/logs \
    bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

USER www-data

RUN composer run-script post-autoload-dump || true

COPY --chown=www-data:www-data docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
USER root
RUN chmod +x /usr/local/bin/docker-entrypoint.sh
USER www-data

EXPOSE 80 9000
ENTRYPOINT ["docker-entrypoint.sh"]
CMD ["php-fpm"]
