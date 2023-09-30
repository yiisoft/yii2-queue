help:			## Display help information
	@fgrep -h "##" $(MAKEFILE_LIST) | fgrep -v fgrep | sed -e 's/\\$$//' | sed -e 's/##//'

build:			## Build an image from a docker-compose file. Params: {{ v=5.6 }}. Default latest PHP 5.6
	@cp -n .env.example .env
	PHP_VERSION=$(filter-out $@,$(v)) docker-compose up -d --build

test:			## Run tests. Params: {{ v=5.6 }}. Default latest PHP 5.6
	PHP_VERSION=$(filter-out $@,$(v)) docker-compose build --pull yii2-queue-php
	PHP_VERSION=$(filter-out $@,$(v)) docker-compose run yii2-queue-php vendor/bin/phpunit --colors=always -v --debug
	make down

down:			## Stop and remove containers, networks
	docker-compose down

benchmark:		## Run benchmark. Params: {{ v=5.6 }}. Default latest PHP 5.6
	PHP_VERSION=$(filter-out $@,$(v)) docker-compose build --pull yii2-queue-php
	PHP_VERSION=$(filter-out $@,$(v)) docker-compose run yii2-queue-php tests/yii benchmark/waiting
	make down

sh:			## Enter the container with the application
	docker exec -it yii2-queue-php bash

check-cs:
	docker-compose build php72
	docker-compose run php72 php-cs-fixer fix --diff --dry-run
	docker-compose down

clean:
	docker-compose down
	rm -rf tests/runtime/*
	rm -f .php_cs.cache
	rm -rf composer.lock
	rm -rf vendor/

clean-all: clean
	sudo rm -rf tests/runtime/.composer*
