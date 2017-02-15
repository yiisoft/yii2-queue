Gearman Driver
==============

Driver works with Gearman queues.

Configuration example:

```php
return [
    'bootstrap' => ['queue'],
    'components' => [
        'queue' => [
            'class' => \zhuravljov\yii\queue\Queue::class,
            'driver' => [
                'class' => \zhuravljov\yii\queue\gearman\Driver::class,
                'host' => 'localhost',
                'port' => 4730,
                'channel' => 'my_queue',
            ],
        ],
    ],
];
```

Console
-------

Console is used to process queued tasks.

```bash
yii queue/listen
```

`listen` command launches a daemon which infinitely queries the queue. If there are new tasks they're immediately
obtained and executed. This method is most effificient when command is properly daemonized via supervisor such as
`supervisord`.
