AMQP Interop
============

このドライバは RabbitMQ のキューによって動作します。

[amqp interop](https://github.com/queue-interop/queue-interop#amqp-interop) 互換のトランスポート、
例えば `enqueue/amqp-lib` パッケージを必要とします。

利点:

* amqp interop 互換のトランスポートであれば、何でも動作します。例えば、

    * [PHP amqp extension](https://github.com/pdezwart/php-amqp) に基づく [enqueue/amqp-ext](https://github.com/php-enqueue/amqp-ext)
    * [php-amqplib/php-amqplib](https://github.com/php-amqplib/php-amqplib) に基づく [enqueue/amqp-lib](https://github.com/php-enqueue/amqp-lib)
    * [bunny](https://github.com/jakubkulhan/bunny) に基づく [enqueue/amqp-bunny](https://github.com/php-enqueue/amqp-bunny)

* 優先度をサポート
* 遅延をサポート
* TTR をサポート
* 試行回数をサポート
* 新しいオプション: vhost, connection_timeout, qos_prefetch_count 等々
* Secure (SSL) AMQP 接続をサポート
* DSN を設定できる: 例えば、amqp:, amqps: または amqp://user:pass@localhost:1000/vhost

構成例:

```php
return [
    'bootstrap' => [
        'queue', // コンポーネントが自身のコンソール・コマンドを登録します
    ],
    'components' => [
        'queue' => [
            'class' => \yii\queue\amqp_interop\Queue::class,
            'port' => 5672,
            'user' => 'guest',
            'password' => 'guest',
            'queueName' => 'queue',
            'driver' => yii\queue\amqp_interop\Queue::ENQUEUE_AMQP_LIB,

            // または
            'dsn' => 'amqp://guest:guest@localhost:5672/%2F',

            // または、上記と同じ
            'dsn' => 'amqp:',
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
