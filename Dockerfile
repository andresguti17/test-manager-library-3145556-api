FROM php:8.4-cli

RUN apt-get update \
    && apt-get install -y --no-install-recommends libpq-dev unzip \
    && docker-php-ext-install pdo_pgsql \
    && rm -rf /var/lib/apt/lists

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-interaction --prefer-dist --no-progress --ignore-platform-reqs --no-scripts

COPY . .
RUN chmod +x entrypoint.sh \
    && mkdir -p /var/covers storage/app/covers \
    && chown -R www-data:www-data storage bootstrap/cache /var/covers

EXPOSE 8000
CMD ["./entrypoint.sh"]
