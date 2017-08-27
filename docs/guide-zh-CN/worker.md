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

以守护进程模式启动的Worker使用 `queue/listen` 命令支持 [File]、 [Db]、 [Redis]、 [RabbitMQ]、 [Beanstalk]、 [Gearman] 驱动。 有关其他参数，请参阅驱动程序指南。

[File]: driver-file.md
[Db]: driver-db.md
[Redis]: driver-redis.md
[RabbitMQ]: driver-amqp.md
[Beanstalk]: driver-beanstalk.md
[Gearman]: driver-gearman.md

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
