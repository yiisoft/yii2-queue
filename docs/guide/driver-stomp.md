Stomp Driver
===============


This driver works with ActiveMQ queues.

It requires the `enqueue/stomp` package.

Configuration example:

```php
return [
    'bootstrap' => [
        'queue', // The component registers its own console commands
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

Console
-------

A console command is used to execute queued jobs.

```sh
yii queue/listen [timeout]
```

The `listen` command launches a daemon which infinitely queries the queue. If there are new tasks
they're immediately obtained and executed. The `timeout` parameter specifies the number of seconds to sleep between
querying the queue. This method is most efficient when the command is properly daemonized via
[supervisor](worker.md#supervisor) or [systemd](worker.md#systemd).
