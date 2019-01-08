File Driver
===========

The file driver uses files to store queue data.

Configuration example:

```php
return [
    'bootstrap' => [
        'queue', // The component registers its own console commands
    ],
    'components' => [
        'queue' => [
            'class' => \yii\queue\file\Queue::class,
            'path' => '@runtime/queue',
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
