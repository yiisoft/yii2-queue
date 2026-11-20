Worker starting control
=======================

Supervisor
----------

Supervisor 是Linux的进程监视器。  
它会自动启动您的控制台进程。  
安装在Ubuntu上，你需要运行命令:

```sh
sudo apt-get install supervisor
```

Supervisor 配置文件通常可用 `/etc/supervisor/conf.d`。
你可以创建任意数量的配置文件。

配置示例:

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

在这种情况下，Supervisor 会启动4个 `queue/listen` worker。输出将写入相应日志文件。

有关 Supervisor 配置和使用的更多信息，请参阅[文档](http://supervisord.org)。

以守护进程模式启动的Worker使用 `queue/listen` 命令支持 [File]、 [Db]、 [Redis]、 [AMQP Interop]、 [Beanstalk]、 [Gearman] 驱动。 有关其他参数，请参阅驱动程序指南。

[File]: driver-file.md
[Db]: driver-db.md
[Redis]: driver-redis.md
[AMQP Interop]: driver-amqp-interop.md
[Beanstalk]: driver-beanstalk.md
[Gearman]: driver-gearman.md

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

Systemd is an init system used in Linux to bootstrap the user space. To configure workers startup
using systemd, create a config file named `yii-queue@.service` in `/etc/systemd/system` with
the following contents:

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

You need to reload systemd in order for it to re-read configuration:

```sh
systemctl daemon-reload
```

Set of commands to control workers:

```sh
# To start two workers
systemctl start yii-queue@1 yii-queue@2

# To get status of running workers
systemctl status "yii-queue@*"

# To stop the worker
systemctl stop yii-queue@2

# To stop all running workers
systemctl stop "yii-queue@*"

# To start two workers at system boot
systemctl enable yii-queue@1 yii-queue@2
```

To learn all features of systemd, check its [documentation](https://freedesktop.org/wiki/Software/systemd/#manualsanddocumentationforusersandadministrators).

Cron
----

您可以用cron开始worker。需要使用 `queue/run` 命令。只要队列包含作业，它就能进行执行。

配置示例: 

```sh
* * * * * /usr/bin/php /var/www/my_project/yii queue/run
```

在这种情况下，cron将每分钟启动一次命令。

`queue/run` 命令支持 [File]、[Db]、[Redis]、[Beanstalk]、[Gearman]驱动。有关其他选项，请参阅驱动程序指南。

[File]: driver-file.md
[Db]: driver-db.md
[Redis]: driver-redis.md
[Beanstalk]: driver-beanstalk.md
[Gearman]: driver-gearman.md
