# syntax=docker/dockerfile:1

# ---- Composer deps (build stage) ----
FROM composer:2 AS vendor
WORKDIR /app

COPY composer.json composer.lock ./
RUN composer install \
    --no-dev \
    --no-interaction \
    --prefer-dist \
    --no-progress

# ---- Runtime (Apache + PHP) ----
FROM php:8.3-apache

# Install required PHP extensions and enable useful Apache modules
RUN set -eux; \
    docker-php-ext-install pdo pdo_mysql opcache; \
    a2enmod headers rewrite

# Opcache: production-lean settings
RUN set -eux; \
    { \
    echo 'opcache.enable=1'; \
    echo 'opcache.enable_cli=0'; \
    echo 'opcache.memory_consumption=128'; \
    echo 'opcache.interned_strings_buffer=16'; \
    echo 'opcache.max_accelerated_files=10000'; \
    echo 'opcache.validate_timestamps=0'; \
    } > /usr/local/etc/php/conf.d/opcache-recommended.ini

WORKDIR /var/www/html

# Copy application code
COPY . .
# Bring in Composer vendor from the build stage
COPY --from=vendor /app/vendor ./vendor

# Ownership for Apache
RUN chown -R www-data:www-data /var/www/html

EXPOSE 80

# Runtime envs to set in Coolify
# DB_HOST=localhost
# DB_NAME=ayuda_db
# DB_USER=root
# DB_PASS=
# JWT_SECRET=change_me
# JWT_EXPIRES=3600

# Apache starts by default via base image CMD
