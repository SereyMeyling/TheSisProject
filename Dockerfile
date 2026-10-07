# ============================================================
# Stage 1: Build frontend assets
# ============================================================
FROM node:20-bookworm AS frontend

WORKDIR /app

COPY package.json package-lock.json ./

RUN npm ci

COPY webpack.mix.js ./
COPY resources ./resources
COPY public ./public

RUN npm run production


# ============================================================
# Stage 2: Laravel + Apache
# ============================================================
FROM php:8.3-apache

WORKDIR /var/www/html

# ------------------------------------------------------------
# Install system dependencies
# ------------------------------------------------------------
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    curl \
    libpng-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    libzip-dev \
    libicu-dev \
    libxml2-dev \
    libonig-dev \
    && rm -rf /var/lib/apt/lists/*

# ------------------------------------------------------------
# Configure PHP extensions
# ------------------------------------------------------------
RUN docker-php-ext-configure gd \
    --with-freetype \
    --with-jpeg

RUN docker-php-ext-install -j$(nproc) \
    pdo_mysql \
    mbstring \
    exif \
    pcntl \
    bcmath \
    gd \
    zip \
    intl \
    xml

# ------------------------------------------------------------
# Enable Apache rewrite
# ------------------------------------------------------------
RUN a2enmod rewrite

# ------------------------------------------------------------
# Install Composer
# ------------------------------------------------------------
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# ------------------------------------------------------------
# Copy Laravel project
# ------------------------------------------------------------
COPY . .

# ------------------------------------------------------------
# Copy compiled frontend assets
# ------------------------------------------------------------
COPY --from=frontend /app/public ./public

# ------------------------------------------------------------
# Install PHP dependencies
# ------------------------------------------------------------
RUN composer install \
    --no-dev \
    --no-interaction \
    --prefer-dist \
    --optimize-autoloader

# ------------------------------------------------------------
# Laravel storage directories
# ------------------------------------------------------------
RUN mkdir -p \
    storage/framework/cache \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    bootstrap/cache

# ------------------------------------------------------------
# Permissions
# ------------------------------------------------------------
RUN chown -R www-data:www-data \
    storage \
    bootstrap/cache

RUN chmod -R 775 \
    storage \
    bootstrap/cache

# ------------------------------------------------------------
# Create Laravel public storage link
# ------------------------------------------------------------
RUN php artisan storage:link || true

# ------------------------------------------------------------
# Apache configuration
# ------------------------------------------------------------
COPY docker/apache.conf /etc/apache2/sites-available/000-default.conf

# ------------------------------------------------------------
# Render uses port 10000 by default
# ------------------------------------------------------------
RUN sed -i 's/Listen 80/Listen 10000/' /etc/apache2/ports.conf \
    && sed -i 's/<VirtualHost \*:80>/<VirtualHost *:10000>/' \
    /etc/apache2/sites-available/000-default.conf

EXPOSE 10000

CMD ["apache2-foreground"]
