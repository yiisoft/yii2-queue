build:
	@cp -n .env.example .env
	docker-compose pull
	docker-compose build --pull

test: test74 test73 test72 test71 test70 test56
test74:
	docker-compose build --pull php74
	docker-compose run php74 vendor/bin/phpunit --colors=always -v --debug
	docker-compose down
test73:
	docker-compose build --pull php73
	docker-compose run php73 vendor/bin/phpunit --colors=always -v --debug
	docker-compose down
test72:
	make clean
	docker-compose build --no-cache --pull php72
	docker-compose run php72 vendor/bin/phpunit --colors=always -v --debug
	docker-compose down
test71:
	docker-compose build --pull php71
	docker-compose run php71 vendor/bin/phpunit --colors=always -v --debug
	docker-compose down
test70:
	docker-compose build --pull php70
	docker-compose run php70 vendor/bin/phpunit --colors=always -v --debug
	docker-compose down
test56:
	docker-compose build --pull php56
	docker-compose run php56 vendor/bin/phpunit --colors=always -v --debug
	docker-compose down

benchmark: benchmark74 benchmark73 benchmark72 benchmark71 benchmark70 benchmark56
benchmark74:
	docker-compose build --pull php74
	docker-compose run php74 tests/yii benchmark/waiting
	docker-compose down
benchmark73:
	docker-compose build --pull php73
	docker-compose run php73 tests/yii benchmark/waiting
	docker-compose down
benchmark72:
	docker-compose build --pull php72
	docker-compose run php72 tests/yii benchmark/waiting
	docker-compose down
benchmark71:
	docker-compose build --pull php71
	docker-compose run php71 tests/yii benchmark/waiting
	docker-compose down
benchmark70:
	docker-compose build --pull php70
	docker-compose run php70 tests/yii benchmark/waiting
	docker-compose down
benchmark56:
	docker-compose build --pull php56
	docker-compose run php56 tests/yii benchmark/waiting
	docker-compose down

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
