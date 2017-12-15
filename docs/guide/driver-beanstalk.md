Beanstalk Driver
================

The driver works with Beanstalk queues.

Configuration example:

```php
return [
    'bootstrap' => [
        'queue', // The component registers own console commands
    ],
    'components' => [
        'queue' => [
            'class' => \yii\queue\beanstalk\Queue::class,
            'host' => 'localhost',
            'port' => 11300,
            'tube' => 'queue',
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
they're immediately obtained and executed. `timeout` parameter is a number of seconds to wait a job.
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
yii queue/remove [id]
```

`remove` command removes a job.
