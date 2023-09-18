FROM php:7.4-cli

RUN apt-get update \
    && apt-get install -y unzip curl zlib1g-dev libzip-dev libicu-dev libpq-dev libgearman-dev

RUN docker-php-ext-install zip pcntl bcmath pdo_mysql intl pdo_pgsql

RUN pecl install igbinary gearman \
    && docker-php-ext-enable igbinary gearman

COPY --from=cytopia/php-cs-fixer:2-php7.4 /usr/bin/php-cs-fixer /usr/local/bin/php-cs-fixer
COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer

COPY . /code
WORKDIR /code

ENTRYPOINT ["tests/docker/php/entrypoint.sh"]
CMD ["sleep", "infinity"]
