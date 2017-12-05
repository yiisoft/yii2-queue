RabbitMQ Driver
===============

__**Note:** The driver hsa been deprecated since 2.0.2 and will be removed in 3.0. Consider using [amqp_interop](driver-amqp-interop.md) driver instead._

The driver works with RabbitMQ queues.

In order for it to work you should add `php-amqplib/php-amqplib` package to your project.

Configuration example:

```php
return [
    'bootstrap' => [
        'queue', // The component registers own console commands
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
