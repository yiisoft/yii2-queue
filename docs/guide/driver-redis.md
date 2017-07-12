Redis Driver
============

The driver uses Redis to store queue data.

You have to add `yiisoft/yii2-redis` extension to your application in order to use it.

Configuration example:

```php
return [
    'bootstrap' => [
        'queue', // The component registers own console commands
    ],
    'components' => [
        'queue' => [
            'class' => \zhuravljov\yii\queue\redis\Queue::class,
            'redis' => 'redis', // connection ID
            'channel' => 'queue', // queue channel
        ],
    ],
];
```

Console
-------

Console command is used to execute tasks.

```sh
yii queue/listen [wait]
```

`listen` command launches a daemon which infinitely queries the queue. If there are new tasks
they're immediately obtained and executed. `wait` is time in seconds to wait between querying
a queue next time. This method is most efficient when command is properly daemonized via
[supervisor](worker.md#supervisor).

```sh
yii queue/run
```

`run` command obtains and executes tasks in a loop until queue is empty. Works well with
[cron](worker.md#cron).

`run` and `listen` commands have options:

- `--verbose`, `-v`: print executing statuses into console.
- `--isolate`: verbose mode of a job execute. If enabled, execute result of each job will be printed.
- `--color`: highlighting for verbose mode.

```sh
yii queue/info
```

`info` command prints out information about queue status.
