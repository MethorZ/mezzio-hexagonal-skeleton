FROM php:8.5.12-cli-trixie
RUN apt-get update \
    && apt-get install -y --no-install-recommends default-mysql-client git unzip \
    && docker-php-ext-install pdo_mysql \
    && rm -rf /var/lib/apt/lists/*
COPY --from=composer:2.10.3 /usr/bin/composer /usr/bin/composer
