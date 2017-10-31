Worker starting control
=======================

Supervisor
----------

Supervisor is process monitor for Linux. It automatically starts your console processes. For install
on Ubuntu you need run command:

```sh
sudo apt-get install supervisor
```

Supervisor config files usually available in `/etc/supervisor/conf.d`. You can create any number of
config files.

Config example:

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

In this case Supervisor must starts 4 `queue/listen` workers. Worker output will write into log
file.

For more info about Supervisor's configure and usage see [documentation](http://supervisord.org).

Worker starting in daemon mode with `queue/listen` command supports [File], [Db], [Redis],
[RabbitMQ], [Beanstalk], [Gearman] drivers. For additional options see driver guide.

[File]: driver-file.md
[Db]: driver-db.md
[Redis]: driver-redis.md
[RabbitMQ]: driver-amqp.md
[Beanstalk]: driver-beanstalk.md
[Gearman]: driver-gearman.md

Systemd
-------

Systemd is an init system used in Linux to bootstrap the user space. To configure a start of workers
using systemd, create config file named `yii-queue@.service` in `/etc/systemd/system`, that
will contain:

```conf
[Unit]
Description=Yii Queue Worker %I
After=network.target
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

You need to reload systemd, so it tries to apply the unit:

```sh
systemctl daemon-reload
```

Set of commands to control of the workers:

```sh
# To start two workers
systemctl start yii-queue@1 yii-queue@2

# To get status of running workers
systemctl status "yii-queue@*"

# To stop the worker
systemctl stop yii-queue@2

# To stop all running workers
systemctl stop "yii-queue@*"

# To start two workers at boot
systemctl enable yii-queue@1 yii-queue@2
```

To learn all features of systemd, see systemd documentation.

Cron
----

You can start worker using cron. You have to use `queue/run` command. It works as long as queue
contains jobs.

Config example: 

```sh
* * * * * /usr/bin/php /var/www/my_project/yii queue/run
```

In this case cron will start the command every minute. 

`queue/run` command is supported by [File], [Db], [Redis], [Beanstalk], [Gearman] drivers.
For additional options see driver guide.

[File]: driver-file.md
[Db]: driver-db.md
[Redis]: driver-redis.md
[Beanstalk]: driver-beanstalk.md
[Gearman]: driver-gearman.md
