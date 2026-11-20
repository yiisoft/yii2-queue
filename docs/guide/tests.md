Tests
=====

Environment
-----------

To run tests you need Docker, Docker Compose and `make`. The Compose file is `docker-compose.yml` in the project root. It starts the application container and the services the drivers need, including MySQL, PostgreSQL, Redis, RabbitMQ, Beanstalk, Gearman, ActiveMQ and LocalStack for AWS SQS.

The PHP version is selected with `PHP_VERSION`. The default is `8.3`. The test workflow runs `8.3`, `8.4` and `8.5`.

Running Tests
-------------

```bash
# PHP 8.3
make test

# another supported version, for example 8.4
make test v=8.4
```

`make test` builds the image, creates the LocalStack SQS queues, runs PHPUnit and stops the containers.

To run a single test file, start the stack and call PHPUnit in the application container:

```bash
make build v=8.4
docker compose run yii2-queue-php vendor/bin/phpunit tests/drivers/sqs/QueueTest.php
make down
```

AWS SQS tests use the LocalStack service from `docker-compose.yml`. The default endpoint, queue URLs and credentials are set there. Override `AWS_KEY`, `AWS_SECRET`, `AWS_REGION`, `AWS_SQS_ENDPOINT` and `AWS_SQS_URL` only when you want to point the tests at another SQS endpoint.
