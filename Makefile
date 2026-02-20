help:			## Display help information
	@fgrep -h "##" $(MAKEFILE_LIST) | fgrep -v fgrep | sed -e 's/\\$$//' | sed -e 's/##//'

build:			## Build an image from a docker-compose file. Params: {{ v=8.3 }}. Default latest PHP 8.3
	PHP_VERSION=$(filter-out $@,$(v)) docker compose up -d --build
	make create-sqs-queue
	make create-sqs-fifo-queue

test:			## Run tests. Params: {{ v=8.3 }}. Default latest PHP 8.3
	make build
	PHP_VERSION=$(filter-out $@,$(v)) docker compose run yii2-queue-php vendor/bin/phpunit --coverage-clover coverage.xml
	make down

down:			## Stop and remove containers, networks
	docker compose down

benchmark:		## Run benchmark. Params: {{ v=8.3 }}. Default latest PHP 8.3
	PHP_VERSION=$(filter-out $@,$(v)) docker compose build --pull yii2-queue-php
	PHP_VERSION=$(filter-out $@,$(v)) docker compose run yii2-queue-php tests/yii benchmark/waiting
	make down

sh:			## Enter the container with the application
	docker exec -it yii2-queue-php sh

static-analyze:		## Run code static analyze. Params: {{ v=8.3 }}. Default latest PHP 8.3
	make build v=$(filter-out $@,$(v))
	docker exec yii2-queue-php sh -c "php -v && composer update && vendor/bin/phpstan analyse --memory-limit 512M"
	make down

clean:
	docker compose down
	rm -rf tests/runtime/*
	rm -rf composer.lock
	rm -rf vendor/

clean-all: clean
	sudo rm -rf tests/runtime/.composer*

create-sqs-queue:	## Create SQS queue
	docker exec yii2-queue-localstack sh -c "awslocal sqs create-queue --queue-name yii2-queue"

create-sqs-fifo-queue:	## Create SQS FIFO queue
	docker exec yii2-queue-localstack sh -c 'awslocal sqs create-queue --queue-name yii2-queue.fifo --attributes "FifoQueue=true"'
