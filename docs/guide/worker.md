Starting Workers
================

Supervisor
----------

[Supervisor](http://supervisord.org) is a process monitor for Linux. It automatically starts
console processes.  On Ubuntu it can be installed with this command:

```sh
sudo apt-get install supervisor
```

Supervisor config files are usually available in `/etc/supervisor/conf.d`. You can create any number of
config files there.

Here's an example:

```conf
[program:yii-queue-worker]
process_name=%(program_name)s_%(process_num)02d
command=/usr/bin/php /var/www/my_project/yii queue/listen --verbose=1 --color=0
autostart=true
autorestart=true
user=www-data
numprocs=4
redirect_stderr=true
stdout_logfile=/var/www/my_project/log/yii-queue-worker.log
```

In this case Supervisor should start 4 `queue/listen` workers. The worker output will be written
to the specified log file.

For more info about Supervisor's configuration and usage see its [documentation](http://supervisord.org).

Note that worker daemons started with `queue/listen` are only supported by the [File], [Db], [Redis],
[AMQP Interop], [Beanstalk], [Gearman] and [AWS SQS] drivers. For additional options see driver guide.

[File]: driver-file.md
[Db]: driver-db.md
[Redis]: driver-redis.md
[AMQP Interop]: driver-amqp-interop.md
[Beanstalk]: driver-beanstalk.md
[Gearman]: driver-gearman.md
[AWS SQS]: driver-sqs.md

Docker
------

[Docker Compose](https://docs.docker.com/compose/) can keep `queue/listen` workers running next to the application. Use the same image as the web (php-fpm) service and start one worker process in the container:

```yaml
services:
  queue:
    image: my-app-php # the same image the web (php-fpm) service uses
    command: php /var/www/my_project/yii queue/listen --verbose=1 --color=0
    restart: unless-stopped
    depends_on:
      - mysql
```

A worker container runs a single process, so one Compose service maps to one queue component. An application with several queue components defines a service for each of them.

To run several workers of the same queue, duplicate the service or scale it:

```sh
docker compose up --scale queue=3
```

With Docker Swarm, set `deploy.replicas` instead of `--scale`.

`restart: unless-stopped` restarts a stopped worker, the same way Supervisor does with `autorestart=true`. `restart: on-failure` restarts it only when the process exits with an error.

The worker writes to the container stdout, so there is no log file to configure. `docker compose logs -f queue` shows the `--verbose=1` output.

To keep workers out of a plain `docker compose up`, assign them a profile and start that profile explicitly:

```yaml
services:
  queue:
    profiles: ["worker"]
```

```sh
docker compose --profile worker up
```

Systemd
-------

Systemd is another init system used on Linux to bootstrap the user space. To configure workers startup
using systemd, create a config file named `yii-queue@.service` in `/etc/systemd/system` with
the following content:

```conf
[Unit]
Description=Yii Queue Worker %I
After=network.target
# the following two lines only apply if your queue backend is mysql
# replace this with the service that powers your backend
After=mysql.service
Requires=mysql.service

[Service]
User=www-data
Group=www-data
ExecStart=/usr/bin/php /var/www/my_project/yii queue/listen --verbose
Restart=on-failure

[Install]
WantedBy=multi-user.target
```

You need to reload systemd in order to re-read its configuration:

```sh
systemctl daemon-reload
```

Set of commands to control workers:

```sh
# To start two workers
systemctl start yii-queue@1 yii-queue@2

# To get the status of running workers
systemctl status "yii-queue@*"

# To stop a specific worker
systemctl stop yii-queue@2

# To stop all running workers
systemctl stop "yii-queue@*"

# To start two workers at system boot
systemctl enable yii-queue@1 yii-queue@2
```

To learn all features of systemd, check its [documentation](https://freedesktop.org/wiki/Software/systemd/#manualsanddocumentationforusersandadministrators).

Cron
----

You can also start workers using cron. Here you have to use the `queue/run` command.

Config example:

```sh
* * * * * /usr/bin/php /var/www/my_project/yii queue/run
```

In this case cron will run the command every minute.

The `queue/run` command is supported by the [File], [Db], [Redis], [Beanstalk], [Gearman], [AWS SQS] drivers.
For additional options see driver guide.

[File]: driver-file.md
[Db]: driver-db.md
[Redis]: driver-redis.md
[Beanstalk]: driver-beanstalk.md
[Gearman]: driver-gearman.md
[AWS SQS]: driver-sqs.md