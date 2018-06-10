init: clean build
	docker-compose run php72 true
	docker-compose down

test: test-php72 test-php71 test-php70 test-php56
test-php72:
	docker-compose run php72 vendor/bin/phpunit --verbose
	docker-compose down
test-php71:
	docker-compose run php71 vendor/bin/phpunit --verbose
	docker-compose down
test-php70:
	docker-compose run php70 vendor/bin/phpunit --verbose
	docker-compose down
test-php56:
	docker-compose run php56 vendor/bin/phpunit --verbose
	docker-compose down

benchmark: benchmark-php72 benchmark-php71 benchmark-php70 benchmark-php56
benchmark-php72:
	docker-compose run php72 tests/yii benchmark/waiting
	docker-compose down
benchmark-php71:
	docker-compose run php71 tests/yii benchmark/waiting
	docker-compose down
benchmark-php70:
	docker-compose run php70 tests/yii benchmark/waiting
	docker-compose down
benchmark-php56:
	docker-compose run php56 tests/yii benchmark/waiting
	docker-compose down

check-cs:
	docker-compose run php72 php-cs-fixer fix --diff --dry-run
	docker-compose down

build:
	docker-compose pull
	docker-compose build

clean:
	docker-compose down
	sudo rm -rf runtime/*
	sudo rm -f ../.php_cs.cache
	sudo rm -f ../composer.lock
	sudo rm -rf ../vendor/
