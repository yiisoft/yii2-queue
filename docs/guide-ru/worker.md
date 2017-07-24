Управление запуском воркеров
============================

Supervisor
----------

Supervisor — монитор процессов для ОС Linux, он автоматически перезапустит ваши консольные процессы,
если они остановятся. Для установки Supervisor в Ubuntu можно использовать такую команду:

```sh
sudo apt-get install supervisor
```

Конфиги Supervisor обычно находятся в папке `/etc/supervisor/conf.d`. Можно создать любое количество
конфигов.

Пример конфига:

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

Этот пример указывает, что Supervisor должен запустить 4 воркера `queue/listen`, наблюдать за ними,
и автоматически перезапускать их если они будут падать. Весь вывод будет писаться в лог.

Подробнее о настройке и использовании Supervisor читайте в [документации](http://supervisord.org).

Запуск воркера в режиме демона командой `queue/listen` поддерживают драйвера [File], [Db], [Redis],
[RabbitMQ], [Beanstalk], [Gearman]. Дополнительные опции смотрите в описании нужных вам драйверов.

[File]: driver-file.md
[Db]: driver-db.md
[Redis]: driver-redis.md
[RabbitMQ]: driver-amqp.md
[Beanstalk]: driver-beanstalk.md
[Gearman]: driver-gearman.md

Cron
----

Запускать воркеры можно с помощью cron. Для этого удобнее всего использовать команду `queue/run`.
Она работает пока в очереди есть задания.

Пример настройки: 

```sh
* * * * * /usr/bin/php /var/www/my_project/yii queue/run
```

В этом случае cron будет запускать команду каждую минуту.

Команду `queue/run` поддерживают драйвера [File], [Db], [Redis], [Beanstalk], [Gearman].
Дополнительные опции смотрите в описании нужных вам драйверов.

[File]: driver-file.md
[Db]: driver-db.md
[Redis]: driver-redis.md
[Beanstalk]: driver-beanstalk.md
[Gearman]: driver-gearman.md
