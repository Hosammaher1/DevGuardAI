# ==========================================
# DevGuard AI — Production Laravel Dockerfile
# Target Platform: Render Web Service (Free/Standard)
# ==========================================

FROM php:8.2-fpm-alpine

# Set working directory
WORKDIR /var/www/html

# Install required system libraries and build tools
RUN apk add --no-cache \
    nginx \
    gettext \
    libpq-dev \
    libzip-dev \
    curl \
    unzip \
    && docker-php-ext-install -j$(nproc) \
    pdo_pgsql \
    pgsql \
    bcmath \
    opcache \
    zip \
    pcntl \
    && rm -rf /var/cache/apk/*

# Copy Composer binary from official Composer image
COPY --from=composer:2.8 /usr/bin/composer /usr/bin/composer

# Copy composer manifests first to leverage Docker layer caching
COPY composer.json composer.lock ./

# Install production dependencies only (no dev dependencies, no scripts yet)
RUN composer install \
    --no-dev \
    --no-interaction \
    --no-plugins \
    --no-scripts \
    --prefer-dist \
    --optimize-autoloader

# Copy application source code (ignoring .env and secrets per .dockerignore)
COPY . .

# Generate authoritative optimized classmap without executing dev scripts
RUN composer dump-autoload --no-dev --optimize --classmap-authoritative

# Copy Docker service configurations
COPY docker/nginx.conf.template /etc/nginx/nginx.conf.template
COPY docker/php-fpm.conf /usr/local/etc/php-fpm.d/zz-docker.conf
COPY docker/php.ini /usr/local/etc/php/conf.d/custom.ini
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh

# Prepare directory tree and permissions
RUN chmod +x /usr/local/bin/entrypoint.sh \
    && mkdir -p storage/framework/cache/data \
                storage/framework/sessions \
                storage/framework/views \
                storage/app/private \
                storage/app/public \
                storage/logs \
                bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

# Render default port
EXPOSE 10000

# Production runtime defaults
ENV APP_ENV=production \
    APP_DEBUG=false \
    LOG_CHANNEL=stderr \
    DB_CONNECTION=pgsql

# Startup entrypoint
ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
