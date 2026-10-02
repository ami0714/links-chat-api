FROM php:8.4-cli-alpine

# 1. Install system dependencies, including Caddy
RUN apk add --no-cache \
    libpng-dev \
    libjpeg-turbo-dev \
    libwebp-dev \
    freetype-dev \
    libzip-dev \
    zip \
    unzip \
    git \
    supervisor \
    caddy \
    && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install pdo pdo_mysql zip gd

# 2. Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /app
COPY . /app

# 3. Install Laravel dependencies
RUN composer install --no-dev --optimize-autoloader

# 4. Set permissions
RUN chmod -R 777 storage bootstrap/cache

# 5. Copy Caddyfile
COPY Caddyfile /etc/caddy/Caddyfile

# 6. Embed Supervisor configuration
RUN mkdir -p /etc/supervisor.d && \
    printf '%s\n' \
    '[supervisord]' \
    'nodaemon=true' \
    'user=root' \
    'logfile=/dev/null' \
    'logfile_maxbytes=0' \
    '' \
    '[program:laravel]' \
    'command=php artisan serve --host=127.0.0.1 --port=8000' \
    'directory=/app' \
    'autostart=true' \
    'autorestart=true' \
    'stdout_logfile=/dev/stdout' \
    'stdout_logfile_maxbytes=0' \
    'stderr_logfile=/dev/stderr' \
    'stderr_logfile_maxbytes=0' \
    '' \
    '[program:reverb]' \
    'command=php artisan reverb:start --host=127.0.0.1 --port=8080' \
    'directory=/app' \
    'autostart=true' \
    'autorestart=true' \
    'stdout_logfile=/dev/stdout' \
    'stdout_logfile_maxbytes=0' \
    'stderr_logfile=/dev/stderr' \
    'stderr_logfile_maxbytes=0' \
    '' \
    '[program:caddy]' \
    'command=caddy run --config /etc/caddy/Caddyfile --adapter caddyfile' \
    'directory=/app' \
    'autostart=true' \
    'autorestart=true' \
    'stdout_logfile=/dev/stdout' \
    'stdout_logfile_maxbytes=0' \
    'stderr_logfile=/dev/stderr' \
    'stderr_logfile_maxbytes=0' \
    > /etc/supervisord.conf

# 7. Expose the public port (Render will map this)
EXPOSE 10000

CMD ["/usr/bin/supervisord", "-c", "/etc/supervisord.conf"]
