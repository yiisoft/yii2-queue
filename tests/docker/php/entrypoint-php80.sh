#!/bin/sh

set -eu

flock tests/runtime/composer-install.lock composer install --prefer-dist --no-interaction --ignore-platform-req=php
composer require phpunit/phpunit:~7.5.20 --ignore-platform-req=php --dev --with-all-dependencies --no-interaction

tests/yii sqlite-migrate/up --interactive=0

tests/docker/php/mysql-lock.php tests/yii mysql-migrate/up --interactive=0

tests/docker/php/mysql-lock.php tests/yii pgsql-migrate/up --interactive=0

php --version
set -x
exec "$@"
