FROM php:8.4-cli-alpine

# Install sistem dependensi & perpustakaan imej (PNG, JPEG, WebP, FreeType)
RUN apk add --no-cache \
    libpng-dev \
    libjpeg-turbo-dev \
    libwebp-dev \
    freetype-dev \
    libzip-dev \
    zip \
    unzip \
    git \
    && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install pdo pdo_mysql zip gd

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /app
COPY . /app

# Install dependensi Laravel
RUN composer install --no-dev --optimize-autoloader

# Tetapkan kebenaran folder storage
RUN chmod -R 777 storage bootstrap/cache

EXPOSE 8080

# Jalankan server
# Tukar baris CMD asal kepada ini:
CMD  php artisan serve --host=0.0.0.0 --port=8080 && php artisan reverb:start