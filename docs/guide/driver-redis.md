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

Console command is used to execute tasks.

```sh
yii queue/listen [timeout]
```

`listen` command launches a daemon which infinitely queries the queue. If there are new tasks
they're immediately obtained and executed. `timeout` parameter is number of seconds to wait a job.
This method is most efficient when command is properly daemonized via
[supervisor](worker.md#supervisor) or [systemd](worker.md#systemd).

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

```sh
yii queue/clear
```

`clear` command clears a queue.

```sh
yii queue/remove [id]
```

`remove` command removes a job.
