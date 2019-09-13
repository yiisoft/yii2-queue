Stomp ドライバ
==============


このドライバは ActiveMQ キューで動作します。

`enqueue/stomp` パッケージを必要とします。

構成例:

```php
return [
    'bootstrap' => [
        'queue', // コンポーネントが自身のコンソール・コマンドを登録します
    ],
    'components' => [
        'queue' => [
            'class' => \yii\queue\stomp\Queue::class,
            'host' => 'localhost',
            'port' => 61613,
            'queueName' => 'queue',
        ],
    ],
];
```

コンソール
----------

キューに入れられたジョブを実行するためにコンソール・コマンドが使用されます。

```sh
yii queue/listen [timeout]
```

`listen` コマンドは無限にキューを調べ続けるデーモンを起動します。キューに新しいタスクがあると、即座に取得され、実行されます。
`timeout` パラメータはキューを調べる間のスリープの秒数を指定するものです。
このコマンドを [supervisor](worker.md#supervisor) または [systemd](worker.md#systemd) によって適切にデーモン化するのが、
最も効率的な方法です。
