FROM php:7.1-cli

RUN apt-get update \
    && apt-get install -y curl \
    && apt-get install -y zlib1g-dev \
    && docker-php-ext-install zip \
    && apt-get install -y libicu-dev \
    && docker-php-ext-configure intl \
    && docker-php-ext-install intl \
    && docker-php-ext-install pcntl \
    && pecl install igbinary \
    && docker-php-ext-enable igbinary \
    && docker-php-ext-install bcmath \
    && docker-php-ext-install pdo_mysql \
    && apt-get install -y libpq-dev \
    && docker-php-ext-configure pgsql -with-pgsql=/usr/local/pgsql \
    && docker-php-ext-install pdo_pgsql \
    && apt-get -y install libgearman-dev \
    && TMPDIR=$(mktemp -d) \
    && cd $TMPDIR \
    && curl -L https://github.com/wcgallego/pecl-gearman/archive/gearman-2.0.3.tar.gz | tar xzv --strip 1 \
    && phpize \
    && ./configure \
    && make -j$(nproc) \
    && make install \
    && cd - \
    && rm -r $TMPDIR \
    && docker-php-ext-enable gearman \
    && rm -rf /var/lib/apt/lists/* \
    && curl -L -o /tmp/composer-setup.php https://getcomposer.org/installer \
    && php /tmp/composer-setup.php --install-dir=/usr/local/bin --filename=composer \
    && rm /tmp/composer-setup.php \
    && curl -L -o /usr/local/bin/php-cs-fixer https://cs.sensiolabs.org/download/php-cs-fixer-v2.phar \
    && chmod a+x /usr/local/bin/php-cs-fixer

COPY . /code
WORKDIR /code

ENTRYPOINT ["tests/docker/php/entrypoint.sh"]
CMD ["sleep", "infinity"]