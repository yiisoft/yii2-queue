Beanstalk Driver
================

The driver works with Beanstalk queues.

Configuration example:

```php
return [
    'bootstrap' => ['queue'],
    'components' => [
        'queue' => [
            'class' => \zhuravljov\yii\queue\beanstalk\Queue::class,
            'host' => 'localhost',
            'port' => 11300,
            'tube' => 'queue',
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
yii queue/info
```

`info` command prints out information about queue status.
