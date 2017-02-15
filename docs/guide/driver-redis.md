Redis Driver
============

The driver uses Redis to store queue data.

You have to add `yiisoft/yii2-redis` extension to your application in order to use it.

Configuration example:

```php
return [
    'bootstrap' => ['queue'],
    'components' => [
        'queue' => [
            'class' => \zhuravljov\yii\queue\Queue::class,
            'driver' => [
                'class' => \zhuravljov\yii\queue\redis\Driver::class,
                'redis' => 'redis', // connection ID
                'channel' => 'queue', // queue channel
            ],
        ],
    ],
];
```

Console command is used to execute tasks.

```bash
yii queue/run
```

`run` command obtains and executes tasks in a loop until queue is empty. Works well with cron.

```bash
yii queue/listen
```

`listen` command launches a daemon which infinitely queries the queue. If there are new tasks they're immediately
obtained and executed. This method is most effificient when command is properly daemonized via supervisor such as
`supervisord`.

```bash
yii queue/stats
```

`stats` command prints out statistics.

