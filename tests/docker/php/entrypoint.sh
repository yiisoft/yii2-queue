#!/bin/sh

set -eu

flock tests/runtime/composer-install.lock composer install --prefer-dist --no-interaction

tests/yii sqlite-migrate/up --interactive=0

tests/docker/wait-for-it.sh mysql:3306 -t 180
tests/docker/php/mysql-lock.php tests/yii mysql-migrate/up --interactive=0

tests/docker/wait-for-it.sh postgres:5432 -t 180
tests/docker/php/mysql-lock.php tests/yii pgsql-migrate/up --interactive=0

tests/docker/wait-for-it.sh redis:6379 -t 180

tests/docker/wait-for-it.sh rabbitmq:5672 -t 180

tests/docker/wait-for-it.sh beanstalk:11300 -t 180

tests/docker/wait-for-it.sh gearmand:4730 -t 180

tests/docker/wait-for-it.sh activemq:61613 -t 180

php --version
set -x
exec "$@"
