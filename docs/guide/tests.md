Tests
=====

Environment
-----------

In order to run tests, you need to install Docker, Docker Compose and the `make` utility. Docker configuration files are 
in `tests/docker` and Docker Compose file is `tests/docker-compose.yml`. There are configurations for different versions 
of PHP (5.6, 7.0, 7.1, 7.2, 7.3). You need to create `.env` file to specify where the `docker-compose.yml` file is. You 
can create `.env` file from `.env.example` in the root directory of the project.

Running Tests
-------------

To run tests execute the following command:

```bash
# for all PHP versions
make test

# for PHP 7.3 only
make test73
```

If you need to pass options to `phpunit` use the following commands (for example, to run only one test file):
```bash
docker-compose build --pull php73
docker-compose run php73 vendor/bin/phpunit tests\\drivers\\sqs\\QueueTest /code/tests/drivers/sqs/QueueTest.php
docker-compose down
```

Some tests can be disabled by default for various reasons (for example, the AWS SQS test require a queue set up in AWS).
The test checks `AWS_SQS_ENABLED` environment variable (see `\tests\drivers\sqs\QueueTest::setUp`). If you want to 
run that test you need to set this variable to `1`. You can specify environment variables that you need to pass to 
the container in the `.env` file in the base directory (see `.env.example`). AWS SQS test requires queue credentials that you also 
need to pass to the container via `.env` file (see `tests/app/config/main.php`).

```bash
# .env

AWS_SQS_ENABLED=1
AWS_KEY=KEY
AWS_SECRET=SECRET
AWS_REGION=us-east-1
AWS_SQS_URL=https://sqs.us-east-1.amazonaws.com/234888945020/queue1
```

```bash
# AWS SQS test will not be skipped now
make test73
```
