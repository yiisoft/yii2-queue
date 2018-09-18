RabbitMQ Driver
===============

**Note:** This driver has been deprecated since 2.0.2 and will be removed in 2.1.
Consider using the [amqp_interop](driver-amqp-interop.md) driver instead.

This driver works with RabbitMQ queues.

It requires the `php-amqplib/php-amqplib` package.

Configuration example:

```php
return [
    'bootstrap' => [
        'queue', // The component registers its own console commands
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

Console
-------

A console command is used to execute queued jobs.

```sh
yii queue/listen
```

The `listen` command launches a daemon which infinitely queries the queue. This method is most
efficient when the command is properly daemonized via [supervisor](worker.md#supervisor)
or [systemd](worker.md#systemd).
