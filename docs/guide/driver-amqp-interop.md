AMQP Interop
============

This driver works with RabbitMQ queues.

It requires an [amqp interop](https://github.com/queue-interop/queue-interop#amqp-interop) compatible
transport, for example the `enqueue/amqp-lib` package.

Advantages:

* It works with any amqp interop compatible transports, such as 

    * [enqueue/amqp-ext](https://github.com/php-enqueue/amqp-ext) based on [PHP amqp extension](https://github.com/pdezwart/php-amqp)
    * [enqueue/amqp-lib](https://github.com/php-enqueue/amqp-lib) based on [php-amqplib/php-amqplib](https://github.com/php-amqplib/php-amqplib)
    * [enqueue/amqp-bunny](https://github.com/php-enqueue/amqp-bunny) based on [bunny](https://github.com/jakubkulhan/bunny)

* Supports priorities
* Supports delays
* Supports ttr
* Supports attempts
* Contains new options like: vhost, connection_timeout, qos_prefetch_count and so on.
* Supports Secure (SSL) AMQP connections.
* Has the ability to set DSN like: amqp:, amqps: or amqp://user:pass@localhost:1000/vhost

Configuration example:

```php
return [
    'bootstrap' => [
        'queue', // The component registers its own console commands
    ],
    'components' => [
        'queue' => [
            'class' => \yii\queue\amqp_interop\Queue::class,
            'port' => 5672,
            'user' => 'guest',
            'password' => 'guest',
            'queueName' => 'queue',
            'driver' => yii\queue\amqp_interop\Queue::ENQUEUE_AMQP_LIB,

            // or
            'dsn' => 'amqp://guest:guest@localhost:5672/%2F',

            // or, same as above
            'dsn' => 'amqp:',
        ],
    ],
];
```

Console
-------

A console command is used to execute queued jobs.

```sh
yii queue/listen
```

The `listen` command launches a daemon which infinitely queries the queue. If there are new tasks
they're immediately obtained and executed. This method is most efficient when the command is properly daemonized via
[supervisor](worker.md#supervisor) or [systemd](worker.md#systemd).
