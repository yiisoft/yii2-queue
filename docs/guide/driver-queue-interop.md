Queue Interop Driver (php-enqueue)
==================================

The driver works with many queue brokers.

You can find full list of supported brokers on the [Queue Interop](https://github.com/queue-interop/queue-interop) page

In order for it to work you need to install and configure one the implementations supported.

Configuration example for the RabbitMQ AMQP:

```php
return [
    'bootstrap' => [
        'queue', // The component registers own console commands
    ],
    'components' => [
        'queue' => [
            'class' => \yii\queue\queue_interop\Queue::class,
            'queueName' => 'queue',
            'factoryClass' => \Enqueue\AmqpLib\AmqpConnectionFactory::class,
            'factoryConfig' => [
                'host' => 'localhost',
                'port' => 5672,
                'user' => 'guest',
                'pass' => 'guest',
                'vhost' => '/',
            ],
        ],
    ],
];
```

Console
-------

Console is used to listen and process queued tasks.

```sh
yii queue/listen
```

`listen` command launches a daemon which infinitely queries the queue. If there are new tasks
they're immediately obtained and executed. This method is most efficient when command is properly
daemonized via [supervisor](worker.md#supervisor).

`listen` command has options:

- `--verbose`, `-v`: print execution status into console.
- `--isolate`: verbose mode of a job execution. If enabled, execution result of each job will be printed.
- `--color`: highlighting for verbose mode.
