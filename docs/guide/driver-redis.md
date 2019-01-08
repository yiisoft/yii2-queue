Redis Driver
============

This driver uses Redis to store queue data.

You have to add the `yiisoft/yii2-redis` extension to your application in order to use it.

Configuration example:

```php
return [
    'bootstrap' => [
        'queue', // The component registers its own console commands
    ],
    'components' => [
        'redis' => [
            'class' => \yii\redis\Connection::class,
            // ...

            // retry connecting after connection has timed out
            // yiisoft/yii2-redis >=2.0.7 is required for this.
            'retries' => 1,
        ],
        'queue' => [
            'class' => \yii\queue\redis\Queue::class,
            'redis' => 'redis', // Redis connection component or its config
            'channel' => 'queue', // Queue channel key
        ],
    ],
];
```

Console
-------

Console commands are used to execute and manage queued jobs.

```sh
yii queue/listen [timeout]
```

The `listen` command launches a daemon which infinitely queries the queue. If there are new tasks
they're immediately obtained and executed. The `timeout` parameter specifies the number of seconds to sleep between
querying the queue. This method is most efficient when the command is properly daemonized via
[supervisor](worker.md#supervisor) or [systemd](worker.md#systemd).

```sh
yii queue/run
```

The `run` command obtains and executes tasks in a loop until the queue is empty. This works well with
[cron](worker.md#cron).

The `run` and `listen` commands have options:

- `--verbose`, `-v`: print execution statuses to console.
- `--isolate`: each task is executed in a separate child process.
- `--color`: enable highlighting for verbose mode.

```sh
yii queue/info
```

The `info` command prints out information about the queue status.

```sh
yii queue/clear
```

The `clear` command clears the queue.

```sh
yii queue/remove [id]
```

The `remove` command removes a job from the queue.
