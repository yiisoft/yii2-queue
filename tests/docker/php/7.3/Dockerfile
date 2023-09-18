FROM php:7.3-cli

RUN apt-get update \
    && apt-get install -y unzip curl zlib1g-dev libzip-dev libicu-dev libpq-dev libgearman-dev

RUN docker-php-ext-install zip pcntl bcmath pdo_mysql intl pdo_pgsql

RUN pecl install igbinary \
    && docker-php-ext-enable igbinary

RUN TMPDIR=$(mktemp -d) \
    && cd $TMPDIR \
    && curl -L https://github.com/wcgallego/pecl-gearman/archive/gearman-2.0.3.tar.gz | tar xzv --strip 1 \
    && phpize \
    && ./configure \
    && make -j$(nproc) \
    && make install \
    && cd - \
    && rm -r $TMPDIR \
    && docker-php-ext-enable gearman

COPY --from=cytopia/php-cs-fixer:2-php7.3 /usr/bin/php-cs-fixer /usr/local/bin/php-cs-fixer
COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer

COPY . /code
WORKDIR /code

ENTRYPOINT ["tests/docker/php/entrypoint.sh"]
CMD ["sleep", "infinity"]
