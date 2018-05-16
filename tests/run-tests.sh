#!/bin/sh

cd `dirname $0`/../

docker-compose -f tests/docker-compose.yml --project-directory . pull
docker-compose -f tests/docker-compose.yml --project-directory . build

docker-compose -f tests/docker-compose.yml --project-directory . run php56 vendor/bin/phpunit --verbose
docker-compose -f tests/docker-compose.yml --project-directory . down

docker-compose -f tests/docker-compose.yml --project-directory . run php70 vendor/bin/phpunit --verbose
docker-compose -f tests/docker-compose.yml --project-directory . down

docker-compose -f tests/docker-compose.yml --project-directory . run php71 vendor/bin/phpunit --verbose
docker-compose -f tests/docker-compose.yml --project-directory . down

docker-compose -f tests/docker-compose.yml --project-directory . run php72 vendor/bin/phpunit --verbose
docker-compose -f tests/docker-compose.yml --project-directory . down