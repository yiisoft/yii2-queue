ファイル・ドライバ
==================

ファイル・ドライバは、キューのデータを保存するのにファイルを使用します。

構成例:

```php
return [
    'bootstrap' => [
        'queue', // コンポーネントが自身のコンソール・コマンドを登録します
    ],
    'components' => [
        'queue' => [
            'class' => \yii\queue\file\Queue::class,
            'path' => '@runtime/queue',
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
