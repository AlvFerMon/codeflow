# Imagen PHP-FPM para el backend Laravel de CodeFlow (desarrollo)
FROM php:8.3-fpm-alpine

# Extensiones que necesita Laravel + MySQL
RUN apk add --no-cache icu-dev libzip-dev zlib-dev oniguruma-dev mysql-client \
    && docker-php-ext-install -j"$(nproc)" pdo_mysql intl zip bcmath opcache \
    && apk del --no-cache icu-dev libzip-dev zlib-dev oniguruma-dev \
    && apk add --no-cache icu-libs libzip

# Composer oficial
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Configuración PHP para desarrollo (subidas de archivos y memoria)
COPY docker/php/php.ini /usr/local/etc/php/conf.d/zz-codeflow.ini

# Script de arranque (se normalizan finales de línea por si se clonó en Windows con CRLF)
COPY docker/php/entrypoint.sh /usr/local/bin/codeflow-entrypoint
RUN sed -i 's/\r$//' /usr/local/bin/codeflow-entrypoint && chmod +x /usr/local/bin/codeflow-entrypoint

WORKDIR /var/www/html

ENTRYPOINT ["codeflow-entrypoint"]
CMD ["php-fpm"]
