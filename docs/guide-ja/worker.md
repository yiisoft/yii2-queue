ワーカを開始する
================

Supervisor
----------

[Supervisor](http://supervisord.org) は Linux 用のプロセス・モニターで、コンソール・プロセスを自動的に開始できます。
Ubuntu では、下記のコマンドによってインストールすることが出来ます。

```sh
sudo apt-get install supervisor
```

Supervisor の構成ファイルは、通常、`/etc/supervisor/conf.d` にあります。
好きな数だけの構成ファイルをそこに作成することが出来ます。

一例を挙げましょう。

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

この場合、Supervisor は 4 個の `queue/listen` ワーカを開始します。
ワーカの出力は指定されたログ・ファイルに書かれます。

Supervisor の構成と使用方法については、その [ドキュメント](http://supervisord.org) を参照して下さい。

`queue/listen` で開始されるワーカ・デーモンがサポートされているのは、[ファイル]、[データベース]、[Redis]、
[AMQP Interop]、[Beanstalk] および [Gearman] のドライバだけであることに留意して下さい。追加のオプションについては、ドライバのガイドを参照して下さい。

[ファイル]: driver-file.md
[データベース]: driver-db.md
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

Systemd は、ユーザ空間をブートストラップするために Linux で使用されるもう一つの初期化システムです。
Systemd を使ってワーカの起動を構成するためには、`/etc/systemd/system` ディレクトリに下記の内容を持った
`yii-queue@.service` という名前の構成ファイルを作成します。

```conf
[Unit]
Description=Yii Queue Worker %I
After=network.target
# 次の2行はキューのバックエンドが mysql である場合にのみ当てはまります
# あなたのバックエンドを支持するサービスで置き換えて下さい
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

構成を再読込するためには、systemd をリロードする必要があります。

```sh
systemctl daemon-reload
```

ワーカを制御する一連のコマンド:

```sh
# 2つのワーカを開始する
systemctl start yii-queue@1 yii-queue@2

# 走っているワーカの状態を取得する
systemctl status "yii-queue@*"

# 特定のワーカを停止する
systemctl stop yii-queue@2

# 走っている全てのワーカを停止する
systemctl stop "yii-queue@*"

# 2つのワーカをシステムのブート時に開始する
systemctl enable yii-queue@1 yii-queue@2
```

Systemd の全ての機能を学ぶためには、その [ドキュメント](https://freedesktop.org/wiki/Software/systemd/#manualsanddocumentationforusersandadministrators) を参照して下さい。

Cron
----

Cron を使ってワーカを開始することも出来ます。その場合は `queue/run` コマンドを使う必要があります。

構成例:

```sh
* * * * * /usr/bin/php /var/www/my_project/yii queue/run
```

上記の場合、cron はコマンドを1分ごとに実行します。

`queue/run` コマンドは、[ファイル], [データベース]、[Redis]、[Beanstalk]、[Gearman] のドライバでサポートされています。
追加のオプションについては、ドライバのガイドを参照して下さい。

[ファイル]: driver-file.md
[データベース]: driver-db.md
[Redis]: driver-redis.md
[Beanstalk]: driver-beanstalk.md
[Gearman]: driver-gearman.md
