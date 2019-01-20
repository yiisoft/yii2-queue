Redis ドライバ
==============

このドライバはキューのデータを保存するのに Redis を使います。

使用するためには `yiisoft/yii2-redis` エクステンションをアプリケーションに追加しなければなりません。

構成例:

```php
return [
    'bootstrap' => [
        'queue', // コンポーネントが自身のコンソール・コマンドを登録します
    ],
    'components' => [
        'redis' => [
            'class' => \yii\redis\Connection::class,
            // ...

            // 接続がタイムアウトした後の接続再試行回数
            // yiisoft/yii2-redis が 2.0.7 以降の場合はこれが必要
            'retries' => 1,
        ],
        'queue' => [
            'class' => \yii\queue\redis\Queue::class,
            'redis' => 'redis', // Redis 接続コンポーネントまたはその構成情報
            'channel' => 'queue', // キュー・チャンネル・キー
        ],
    ],
];
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
