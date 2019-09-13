AWS SQS ドライバ
================

このドライバはキューのデータを保存するのに AWS SQS を使います。

これを使用するためには、あなたのアプリケーションに `aws/aws-sdk-php` エクステンションを追加する必要があります。

構成例:

```php
return [
    'bootstrap' => [
        'queue', // コンポーネントが自身のコンソール・コマンドを登録します
    ],
    'components' => [
        'queue' => [
            'class' => \yii\queue\sqs\Queue::class,
            'url' => '<sqs url>',
            'key' => '<key>',
            'secret' => '<secret>',
            'region' => '<region>',
        ],
    ],
];
```

FIFO キューのための構成例:

```php
return [
    'bootstrap' => [
        'queue', // コンポーネントが自身のコンソール・コマンドを登録します
    ],
    'components' => [
        'queue' => [
            'class' => \yii\queue\sqs\Queue::class,
            'url' => '<sqs url>',
            'key' => '<key>',
            'secret' => '<secret>',
            'region' => '<region>',
            'messageGroupId' => '<Group ID>',
        ],
    ],
];
```

SQS では、FIFO キューのためにはメッセージ・グループ ID が必要です。自分自身で構成するか、"default" という値を使って下さい。

重複回避 ID が自動的に生成されます。そのため、SQS キューでコンテント・ベースの重複回避を有効にしていたとしても、この ID が使われることになります。

コンソール
----------

タスクを実行するためにコンソール・コマンドが使用されます。

```sh
yii queue/listen [timeout]
```

`listen` コマンドが無限にキューを調べ続けるデーモンを起動します。キューに新しいタスクがあると、即座に取得され、実行されます。
`timeout` パラメータはジョブを待つ秒数を指定するものです。
デーモンは、クライアントとキューの間の接続を保持する SQS の "Long Polling" 機能を使います。

**重要** SQS ドライバの `timeout` パラメータは 0 から 20 秒の範囲内になければなりません。

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
yii queue/clear
```

`clear` コマンドはキューをクリアします。


