テスト
======

環境
----

テストを実行するためには、Docker, Docker Compose および `make` ユーティリティをインストールする必要があります。
Docker の構成ファイルは `tests/docker` の下にあり、Docker Compose ファイルは `tests/docker-compose.yml` です。
PHP のバージョン (5.6, 7.0, 7.1, 7.2, 7.3) によって構成ファイルは異なります。`docker-compose.yml` ファイルがある場所を指定するために `.env` ファイルを作成する必要があります。
プロジェクトのルート・ディレクトリにある `.env.example` ファイルから`.env` ファイルを作成すると良いでしょう。

テストを実行する
----------------

テストを実行するためには、次のコマンドを実行します。

```bash
# PHP のすべてのバージョン
make test

# PHP 7.3 のみ
make test73
```

`phpunit` にオプションを渡す必要があるとき (例えば一つのテストファイルだけを実行するとき) は、以下のコマンドを使います。
```bash
docker-compose build --pull php73
docker-compose run php73 vendor/bin/phpunit tests\\drivers\\sqs\\QueueTest /code/tests/drivers/sqs/QueueTest.php
docker-compose down
```

さまざまな理由で、いくつかのテストをデフォルトで無効にしたいことがあるでしょう。(例えば、AWS SQS テストは AWS にキューがセットアップされていることを要求します。)
テスト実行時には `AWS_SQS_ENABLED` 環境変数がチェックされます。(`\tests\drivers\sqs\QueueTest::setUp` を参照。)
AWS SQS テストを実行するためにはこの環境変数を `1` に設定する必要があります。コンテナに渡したい環境変数を
ベース・ディレクトリの `.env` ファイルで指定することが出来ます。(`.env.example` を参照)
AWS SQS テストでは、`.env` ファイルによってキューの認証情報もコンテナに渡す必要があります。(`tests/app/config/main.php` を参照)

```bash
# .env

AWS_SQS_ENABLED=1
AWS_KEY=KEY
AWS_SECRET=SECRET
AWS_REGION=us-east-1
AWS_SQS_URL=https://sqs.us-east-1.amazonaws.com/234888945020/queue1
```

```bash
# これで AWS SQS テストはスキップされなくなる
make test73
```
