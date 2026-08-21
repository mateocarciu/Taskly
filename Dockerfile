# ── Build Stage ──────────────────────────────────────────────
FROM php:8.4-fpm AS build

RUN apt-get update && apt-get install -y \
    git curl libpng-dev libonig-dev libxml2-dev zip unzip libsqlite3-dev \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd pdo_sqlite \
    && rm -rf /var/lib/apt/lists/*

RUN curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y nodejs \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

COPY composer.json composer.lock ./
RUN composer install --no-dev --optimize-autoloader --no-scripts --no-autoloader

COPY package.json package-lock.json ./
RUN npm ci

COPY . .

RUN composer dump-autoload --optimize --no-dev \
    && php artisan config:cache \
    && php artisan route:cache \
    && php artisan view:cache \
    && npm run build

# ── Runtime Stage ────────────────────────────────────────────
FROM php:8.4-fpm

RUN apt-get update && apt-get install -y \
    libpng-dev libonig-dev libxml2-dev libsqlite3-dev nginx supervisor \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd pdo_sqlite \
    && rm -rf /var/lib/apt/lists/*

RUN rm -f /etc/nginx/sites-enabled/default

COPY docker-compose/prod/nginx.conf /etc/nginx/conf.d/taskly.conf
COPY docker-compose/prod/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

WORKDIR /var/www

COPY --from=build /var/www /var/www

RUN rm -rf node_modules tests .git .editorconfig .prettierrc .prettierignore \
    && rm -f docker-compose.prod.yml Dockerfile .dockerignore \
    && rm -f README.md LICENSE \
    && mkdir -p storage/framework/{cache,sessions,views} storage/logs bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache

COPY docker-compose/prod/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

EXPOSE 80

ENTRYPOINT ["/entrypoint.sh"]
