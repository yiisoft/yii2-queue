データベース・ドライバ
======================

データベース・ドライバはキューのデータを保存するのにデータベースを使用します。

データベース・ドライバは以下のオプションをサポートします。

* 優先度 (priorities)
* 遅延実行 (delays)
* 実行時間 (ttr)
* 試行回数 (attempts)

構成例:

```php
return [
    'bootstrap' => [
        'queue', // コンポーネントが自身のコンソール・コマンドを登録します
    ],
    'components' => [
        'db' => [
            'class' => \yii\db\Connection::class, 
            // ...
        ],
        'queue' => [
            'class' => \yii\queue\db\Queue::class,
            'db' => 'db', // DB 接続コンポーネントまたはその構成情報
            'tableName' => '{{%queue}}', // テーブル名
            'channel' => 'default', // キュー・チャンネル・キー
            'mutex' => \yii\mutex\MysqlMutex::class, // クエリ同期のための mutex
        ],
    ],
];
```

データベースにテーブルを追加する必要があります。スキーマ例:

MySQL:

```SQL
CREATE TABLE `queue` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `channel` varchar(255) NOT NULL,
  `job` blob NOT NULL,
  `pushed_at` int(11) NOT NULL,
  `ttr` int(11) NOT NULL,
  `delay` int(11) NOT NULL DEFAULT 0,
  `priority` int(11) unsigned NOT NULL DEFAULT 1024,
  `reserved_at` int(11) DEFAULT NULL,
  `attempt` int(11) DEFAULT NULL,
  `done_at` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `channel` (`channel`),
  KEY `reserved_at` (`reserved_at`),
  KEY `priority` (`priority`)
) ENGINE=InnoDB
```
Postgresql:

```SQL
-- オートインクリメント・フィールドを作成するために必要
CREATE SEQUENCE queue_seq; 

CREATE TABLE queue (
  id bigint NOT NULL DEFAULT NEXTVAL ('queue_seq'),
  channel varchar(255) NOT NULL,
  job bytea NOT NULL,
  pushed_at bigint NOT NULL,
  ttr bigint NOT NULL,
  delay bigint NOT NULL DEFAULT 0,
  priority bigint check (priority > 0) NOT NULL DEFAULT 1024,
  reserved_at bigint ,
  attempt bigint,
  done_at bigint,
  PRIMARY KEY (id)
);
-- 必須ではないが、クエリを高速化するのに良い
CREATE INDEX channel ON queue (channel); 
CREATE INDEX reserved_at ON queue (reserved_at);
CREATE INDEX priority ON queue (priority);
```


マイグレーションが [src/drivers/db/migrations](../../src/drivers/db/migrations) にありますので、利用して下さい。

アプリケーションにマイグレーションを追加するためには、コンソールの構成ファイルを編集して、
[名前空間化されたマイグレーション](http://www.yiiframework.com/doc-2.0/guide-db-migrations.html#namespaced-migrations) を構成して下さい。

```php
'controllerMap' => [
    // ...
    'migrate' => [
        'class' => 'yii\console\controllers\MigrateController',
        'migrationPath' => null,
        'migrationNamespaces' => [
            // ...
            'yii\queue\db\migrations',
        ],
    ],
],
```

そして、`migrate/up` コマンドを発行します。

```sh
yii migrate/up
```

コンソール
----------

キューに入れられたジョブの実行と管理のためにコンソール・コマンドが使用されます。

```sh
yii queue/listen [timeout]
```

`listen` コマンドは無限にキューを調べ続けるデーモンを起動します。キューに新しいタスクがあると、即座に取得され、実行されます。
`timeout` パラメータはキューを調べる間のスリープの秒数を指定するものです。
このコマンドを [supervisor](worker.md#supervisor) または [systemd](worker.md#systemd) によって適切にデーモン化するのが、
最も効率的な方法です。

```sh
yii queue/run
```

`run` コマンドは、キューが空になるまでループして、タスクを取得し、実行します。
[cron](worker.md#cron) に向いた方法です。

`run` および `listen` のコマンドは下記のオプションを持っています。

- `--verbose`, `-v`: 実行の状態をコンソールに表示します。
- `--isolate`: すべてのタスクが独立した子プロセスで実行されます。
- `--color`: 饒舌モードでハイライトを有効にします。

```sh
yii queue/info
```

`info` コマンドはキューの状態について情報を表示します。

```sh
yii queue/clear
```

`clear` コマンドはキューをクリアします。

```sh
yii queue/remove [id]
```

`remove` コマンドはキューからジョブを削除します。
