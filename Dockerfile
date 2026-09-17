FROM php:8.4-cli-alpine

RUN apk add --no-cache postgresql-dev libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql pcntl

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /app
COPY . .

RUN composer install --no-dev --optimize-autoloader --no-interaction

EXPOSE 8000

CMD ["sh", "start.sh"]
