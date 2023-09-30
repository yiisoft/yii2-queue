FROM --platform=linux/amd64 php:8.0.28-cli

RUN apt-get update \
   && apt-get install -y unzip curl zlib1g-dev libzip-dev libicu-dev libpq-dev libgearman-dev

RUN docker-php-ext-install zip pcntl bcmath pdo_mysql intl pdo_pgsql sockets
RUN pecl install igbinary gearman
RUN docker-php-ext-enable igbinary gearman

COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer
COPY --from=cytopia/php-cs-fixer:latest /usr/bin/php-cs-fixer /usr/local/bin/php-cs-fixer

COPY . /code
WORKDIR /code

ENTRYPOINT ["tests/docker/php/entrypoint.sh"]
CMD ["sleep", "infinity"]
