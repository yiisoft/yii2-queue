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
[AMQP Interop], [Beanstalk], [Gearman], [AWS SQS]. Дополнительные опции смотрите в описании нужных вам драйверов.

[File]: driver-file.md
[Db]: driver-db.md
[Redis]: driver-redis.md
[AMQP Interop]: driver-amqp-interop.md
[Beanstalk]: driver-beanstalk.md
[Gearman]: driver-gearman.md
[AWS SQS]: driver-sqs.md

Docker
------

[Docker Compose](https://docs.docker.com/compose/) может держать воркеры `queue/listen` запущенными рядом с приложением. Используйте тот же образ, что и у веб-сервиса (php-fpm), и запускайте в контейнере один процесс воркера:

```yaml
services:
  queue:
    image: my-app-php # тот же образ, что и у веб-сервиса (php-fpm)
    command: php /var/www/my_project/yii queue/listen --verbose=1 --color=0
    restart: unless-stopped
    depends_on:
      - mysql
```

Контейнер воркера выполняет один процесс, поэтому один сервис Compose соответствует одному компоненту очереди. Если в приложении несколько компонентов очереди, для каждого из них нужен свой сервис.

Чтобы запустить несколько воркеров одной очереди, продублируйте сервис или масштабируйте его:

```sh
docker compose up --scale queue=3
```

В Docker Swarm вместо `--scale` задаётся `deploy.replicas`.

`restart: unless-stopped` перезапускает остановленный воркер так же, как `autorestart=true` у Supervisor. `restart: on-failure` перезапускает его только при завершении с ошибкой.

Воркер пишет в stdout контейнера, отдельный файл лога настраивать не нужно. Вывод `--verbose=1` смотрите командой `docker compose logs -f queue`.

Чтобы воркеры не стартовали при обычном `docker compose up`, назначьте им профиль и запускайте его явно:

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
