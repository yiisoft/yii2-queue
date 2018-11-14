Управление запуском воркеров
============================


Supervisor
----------

Supervisor — монитор процессов для ОС Linux. Он автоматически перезапустит ваши консольные процессы,
если они остановятся. Для установки Supervisor в Ubuntu можно использовать следующую команду:

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
[RabbitMQ], [Beanstalk], [Gearman], [AWS SQS]. Дополнительные опции смотрите в описании нужных вам драйверов.

[File]: driver-file.md
[Db]: driver-db.md
[Redis]: driver-redis.md
[RabbitMQ]: driver-amqp.md
[Beanstalk]: driver-beanstalk.md
[Gearman]: driver-gearman.md
[AWS SQS]: driver-sqs.md


Systemd
-------

Systemd - система Linux для инициализации демонов. Чтобы настроить запуск воркеров под управлением systemd, 
создайте конфиг с именем `yii-queue@.service` в папке `/etc/systemd/system` со следующими настройками:

```conf
[Unit]
Description=Yii Queue Worker %I
After=network.target
# Ниже указана зависимость от mysql. Это справедливо если вы используте очереди на основе mysql.
# Если ваш проект использует другой брокер очередей, нужно изменить или дополнить эту секцию.   
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

Перезагрузите systemd, чтобы он увидел новый конфиг, с помощью команды:

```sh
systemctl daemon-reload
```

Набор команд для управления воркерами:

```sh
# Запустить два воркера
systemctl start yii-queue@1 yii-queue@2

# Получить статус запущенных воркеров
systemctl status "yii-queue@*"

# Остановить один воркер
systemctl stop yii-queue@2

# Остановить все воркеры
systemctl stop "yii-queue@*"

# Добавить воркеры в автозагрузку
systemctl enable yii-queue@1 yii-queue@2
```

Чтобы ознакомиться со всеми возможностями systemd, и сделать более тонкую настройку, смотрите
[документацию](https://freedesktop.org/wiki/Software/systemd/#manualsanddocumentationforusersandadministrators).


Cron
----

Запускать воркеры можно с помощью cron. Для этого удобнее всего использовать команду `queue/run`.
Она будет работать, пока в очереди есть задания.

Пример настройки: 

```sh
* * * * * /usr/bin/php /var/www/my_project/yii queue/run
```

В этом случае cron будет запускать команду каждую минуту.

Команду `queue/run` поддерживают драйвера [File], [Db], [Redis], [Beanstalk], [Gearman], [AWS SQS].
Дополнительные опции смотрите в описании нужных вам драйверов.

[File]: driver-file.md
[Db]: driver-db.md
[Redis]: driver-redis.md
[Beanstalk]: driver-beanstalk.md
[Gearman]: driver-gearman.md
[AWS SQS]: driver-sqs.md
