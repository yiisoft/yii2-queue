#!/bin/sh

set -eu

flock tests/runtime/composer-install.lock composer install --prefer-dist --no-interaction

tests/yii sqlite-migrate/up --interactive=0
sleep 20
tests/docker/php/mysql-lock.php tests/yii mysql-migrate/up --interactive=0
sleep 20
tests/docker/php/mysql-lock.php tests/yii pgsql-migrate/up --interactive=0

php --version
set -x
exec "$@"
