#!/bin/sh

flock tests/runtime/composer-install.lock composer install --prefer-dist --no-interaction \
&& tests/yii sqlite-migrate/up --interactive=0 \
&& tests/docker/wait-for-it.sh $MYSQL_HOST:$MYSQL_PORT -t 180 \
&& tests/docker/php/mysql-lock.php tests/yii mysql-migrate/up --interactive=0 \
&& tests/docker/wait-for-it.sh $POSTGRES_HOST:$POSTGRES_PORT -t 180 \
&& tests/docker/php/mysql-lock.php tests/yii pgsql-migrate/up --interactive=0 \
&& tests/docker/wait-for-it.sh $REDIS_HOST:$REDIS_PORT -t 180 \
&& tests/docker/wait-for-it.sh $RABBITMQ_HOST:$RABBITMQ_PORT  -t 180 \
&& tests/docker/wait-for-it.sh $BEANSTALK_HOST:$BEANSTALK_PORT  -t 180 \
&& tests/docker/wait-for-it.sh $GEARMAN_HOST:$GEARMAN_PORT  -t 180 \
&& exec "$@"
