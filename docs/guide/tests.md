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