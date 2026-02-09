# Important! Do not use this image in production!
ARG PHP_VERSION

FROM php:${PHP_VERSION}-cli-alpine

RUN echo https://dl-cdn.alpinelinux.org/alpine/edge/main >> /etc/apk/repositories \
    && echo https://dl-cdn.alpinelinux.org/alpine/edge/community >> /etc/apk/repositories \
    && echo https://dl-cdn.alpinelinux.org/alpine/edge/testing >> /etc/apk/repositories \
    && apk update

RUN apk add git icu-dev libpq-dev gearman-dev libcrypto3 openssl-dev autoconf g++ make linux-headers rabbitmq-c-dev

RUN docker-php-ext-install pcntl bcmath pdo_mysql intl pdo_pgsql sockets
RUN pecl install igbinary pcov amqp xdebug
RUN docker-php-ext-enable igbinary pcov amqp xdebug

# Official gearman package not supported PHP 8.5 now
RUN TMPDIR=$(mktemp -d) \
    && cd $TMPDIR \
    && git clone https://github.com/php/pecl-networking-gearman gearman \
    && cd ./gearman \
    && phpize \
    && ./configure \
    && make -j$(nproc) \
    && make install \
    && cd - \
    && rm -r $TMPDIR \
    && docker-php-ext-enable gearman

COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer

COPY . /code
WORKDIR /code

ENTRYPOINT ["tests/docker/php/entrypoint.sh"]
CMD ["sleep", "infinity"]
