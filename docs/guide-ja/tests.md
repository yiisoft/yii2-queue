テスト
======

環境
----

テストを実行するためには Docker、Docker Compose および `make` が必要です。Compose ファイルはプロジェクトルートの `docker-compose.yml` です。アプリケーションコンテナと、ドライバが必要とする MySQL、PostgreSQL、Redis、RabbitMQ、Beanstalk、Gearman、ActiveMQ、および AWS SQS 用の LocalStack を起動します。

PHP のバージョンは `PHP_VERSION` で選びます。デフォルトは `8.3` です。テストワークフローでは `8.3`、`8.4`、`8.5` を実行します。

テストを実行する
----------------

```bash
# PHP 8.3
make test

# 別のサポート対象バージョン。例: 8.4
make test v=8.4
```

`make test` はイメージをビルドし、LocalStack の SQS キューを作成し、PHPUnit を実行してコンテナを停止します。

一つのテストファイルだけを実行するときは、スタックを起動してアプリケーションコンテナで PHPUnit を呼び出します。

```bash
make build v=8.4
docker compose run yii2-queue-php vendor/bin/phpunit tests/drivers/sqs/QueueTest.php
make down
```

AWS SQS のテストは `docker-compose.yml` の LocalStack を使います。エンドポイント、キュー URL、認証情報のデフォルトはそこに定義されています。別の SQS エンドポイントを使うときだけ `AWS_KEY`、`AWS_SECRET`、`AWS_REGION`、`AWS_SQS_ENDPOINT`、`AWS_SQS_URL` を上書きして下さい。
