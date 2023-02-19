FROM --platform=linux/amd64 php:8.0.28-cli-alpine

RUN echo https://dl-cdn.alpinelinux.org/alpine/edge/main >> /etc/apk/repositories \
    && echo https://dl-cdn.alpinelinux.org/alpine/edge/community >> /etc/apk/repositories \
    && echo https://dl-cdn.alpinelinux.org/alpine/edge/testing >> /etc/apk/repositories \
    && apk update \
    && apk upgrade -a
RUN apk add curl git bash unzip zlib-dev libzip-dev icu-dev libpq-dev gearman-dev gearman-libs autoconf g++ make musl

RUN docker-php-ext-install zip intl pcntl bcmath pdo_mysql pdo_pgsql
RUN pecl install igbinary gearman
RUN docker-php-ext-enable igbinary gearman

RUN curl -L -o /usr/local/bin/php-cs-fixer https://cs.symfony.com/download/php-cs-fixer-v2.phar \
    && chmod a+x /usr/local/bin/php-cs-fixer

COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer

COPY . /code
WORKDIR /code

ENTRYPOINT ["tests/docker/php/entrypoint-php80.sh"]
CMD ["sleep", "infinity"]
