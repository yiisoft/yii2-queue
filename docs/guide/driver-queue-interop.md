Queue Interop Driver
====================

The driver works with many queue brokers.

Full list of supported brokers you can find on the [Queue Interop](https://github.com/queue-interop/queue-interop) page

To get it works you have to install and setup one of the supported implementation.

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

- `--verbose`, `-v`: print executing statuses into console.
- `--isolate`: verbose mode of a job execute. If enabled, execute result of each job will be printed.
- `--color`: highlighting for verbose mode.
