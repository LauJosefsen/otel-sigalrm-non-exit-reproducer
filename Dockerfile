FROM composer:2.8.9 AS composer

FROM php:8.4.7-zts

# Install dependencies for composer
RUN apt-get update && apt-get install -qqy dumb-init git gnupg curl unzip && \
    rm -rf /var/lib/apt/lists/*

# Install opentelemetry
RUN pecl install opentelemetry-1.1.3 && \
    docker-php-ext-enable opentelemetry && \
    docker-php-ext-install pdo pdo_mysql pcntl gettext

COPY --link --from=composer /usr/bin/composer /usr/bin/composer

COPY composer.json composer.lock ./

RUN composer install --no-dev --no-autoloader --no-progress && \
    composer dump-autoload --no-dev


COPY reproducer.php ./
