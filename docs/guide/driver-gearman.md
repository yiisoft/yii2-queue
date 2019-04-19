Gearman Driver
==============

This driver works with Gearman queues.

Configuration example:

```php
return [
    'bootstrap' => [
        'queue', // The component registers its own console commands
    ],
    'components' => [
        'queue' => [
            'class' => \yii\queue\gearman\Queue::class,
            'host' => 'localhost',
            'port' => 4730,
            'channel' => 'my_queue',
        ],
    ],
];
```

Console
-------

Console commands are used to process queued tasks.

```sh
yii queue/listen
```

`listen` command launches a daemon which infinitely queries the queue. If there are new tasks
they're immediately obtained and executed. This method is most efficient when command is properly
daemonized via [supervisor](worker.md#supervisor) or [systemd](worker.md#systemd).

```sh
yii queue/run
```

`run` command obtains and executes tasks in a loop until queue is empty. Works well with
[cron](worker.md#cron).

`run` and `listen` commands have options:

- `--verbose`, `-v`: verbose mode of a job execution. If enabled, the execution results of each job will be printed.
- `--isolate`: isolate mode. It executes a job in a child process.
- `--color`: highlighting for verbose mode.
