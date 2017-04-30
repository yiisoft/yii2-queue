File Driver
===========

File driver uses files to store queue data.

Configuration example:

```php
return [
    'bootstrap' => ['queue'],
    'components' => [
        'queue' => [
            'class' => \zhuravljov\yii\queue\file\Queue::class,
            'path' => '@runtime/queue',
        ],
    ],
];
```

Console
-------

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
