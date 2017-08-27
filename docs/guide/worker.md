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

Config example:

```conf
[Unit]
Description=Yii Jobs Server
After=network.target
After=syslog.target

[Service]
Type=forking
PIDFile=/var/run/yii-queue/master.pid
ExecStart=/path/to/app/yii queue/listen --verbose=1 --color=0 >> /var/logs/yii-queue.log 2>&1
ExecStop=/bin/kill $MAINPID
ExecReload=/bin/kill -USR1 $MAINPID
Restart=always

[Install]
WantedBy=multi-user.target graphical.target
```

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
