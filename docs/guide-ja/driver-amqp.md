RabbitMQ ドライバ
=================

**注意:** このドライバは 2.0.2 以降、非推奨となっており、2.1 では削除される予定です。
代替として [amqp_interop](driver-amqp-interop.md) ドライバの使用を検討して下さい。

このドライバは RabbitMQ のキューによって動作します。

`php-amqplib/php-amqplib` パッケージを必要とします。

構成例:

```php
return [
    'bootstrap' => [
        'queue', // コンポーネントが自身のコンソール・コマンドを登録します
    ],
    'components' => [
        'queue' => [
            'class' => \yii\queue\amqp\Queue::class,
            'host' => 'localhost',
            'port' => 5672,
            'user' => 'guest',
            'password' => 'guest',
            'queueName' => 'queue',
        ],
    ],
];
```

コンソール
----------

キューに入れられたジョブを実行するためにコンソール・コマンドが使用されます。

```sh
yii queue/listen
```

`listen` コマンドが無限にキューを調べ続けるデーモンを起動します。キューに新しいタスクがあると、即座に取得され、実行されます。
このコマンドを [supervisor](worker.md#supervisor) または [systemd](worker.md#systemd) によって適切にデーモン化するのが、
最も効率的な方法です。
