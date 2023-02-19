FROM --platform=linux/amd64 php:8.0.28-cli-alpine

RUN echo https://dl-cdn.alpinelinux.org/alpine/edge/main >> /etc/apk/repositories \
    && echo https://dl-cdn.alpinelinux.org/alpine/edge/community >> /etc/apk/repositories \
    && echo https://dl-cdn.alpinelinux.org/alpine/edge/testing >> /etc/apk/repositories \
    && apk update
RUN apk add bash unzip zlib-dev libzip-dev icu-dev libpq-dev gearman-dev autoconf g++ make musl

RUN docker-php-ext-install zip intl pcntl bcmath pdo_mysql pdo_pgsql
RUN pecl install igbinary gearman
RUN docker-php-ext-enable igbinary gearman

COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer
COPY --from=cytopia/php-cs-fixer:latest /usr/bin/php-cs-fixer /usr/local/bin/php-cs-fixer

COPY . /code
WORKDIR /code

ENTRYPOINT ["tests/docker/php/entrypoint-php80.sh"]
CMD ["sleep", "infinity"]
