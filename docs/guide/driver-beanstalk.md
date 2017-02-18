Beanstalk Driver
================

The driver works with Beanstalk queues.

Configuration example:

```php
return [
    'bootstrap' => ['queue'],
    'components' => [
        'queue' => [
            'class' => \zhuravljov\yii\queue\Queue::class,
            'driver' => [
                'class' => \zhuravljov\yii\queue\beanstalk\Driver::class,
                'host' => 'localhost',
                'port' => 11300,
                'tube' => 'queue',
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
yii queue/listen [delay]
```

`listen` command launches a daemon which infinitely queries the queue. If there are new tasks they're immediately
obtained and executed. `delay` is time in seconds to wait between querying a queue next time.
This method is most effificient when command is properly daemonized via supervisor such as
`supervisord`.
