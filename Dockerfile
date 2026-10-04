FROM php:8.4-cli-alpine

RUN apk add --no-cache \
    libpng-dev \
    libjpeg-turbo-dev \
    libwebp-dev \
    freetype-dev \
    libzip-dev \
    zip \
    unzip \
    git \
    bash \
    && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install pdo pdo_mysql zip gd

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /app
COPY . /app

RUN composer install --no-dev --optimize-autoloader
RUN chmod -R 777 storage bootstrap/cache

EXPOSE 8080

<<<<<<< HEAD
# Jalankan server
# Tukar baris CMD asal kepada ini:
CMD  php artisan serve --host=0.0.0.0 --port=8080 && php artisan reverb:start
=======
CMD php artisan serve --host=0.0.0.0 --port=8080
>>>>>>> a46464244ac02993d6e22088658e7cac88f45a68
