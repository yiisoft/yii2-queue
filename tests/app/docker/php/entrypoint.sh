#!/bin/sh

composer install --prefer-dist --no-interaction \
&& php tests/yii sqlite-migrate/up --interactive=0 \
&& tests/app/docker/wait-for-it.sh $MYSQL_HOST:$MYSQL_PORT \
&& php tests/yii mysql-migrate/up --interactive=0 \
&& tests/app/docker/wait-for-it.sh $POSTGRES_HOST:$POSTGRES_PORT \
&& php tests/yii pgsql-migrate/up --interactive=0 \
&& tests/app/docker/wait-for-it.sh $REDIS_HOST:$REDIS_PORT \
&& tests/app/docker/wait-for-it.sh $RABBITMQ_HOST:$RABBITMQ_PORT \
&& tests/app/docker/wait-for-it.sh $BEANSTALK_HOST:$BEANSTALK_PORT \
&& tests/app/docker/wait-for-it.sh $GEARMAN_HOST:$GEARMAN_PORT \
&& exec "$@"
