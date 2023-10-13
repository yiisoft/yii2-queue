AWS SQS Driver
============

The driver uses AWS SQS to store queue data.

You have to add `aws/aws-sdk-php` extension to your application in order to use it.

Configuration example for standard queues:

```php
return [
    'bootstrap' => [
        'queue', // The component registers own console commands
    ],
    'components' => [
        'queue' => [
            'class' => \yii\queue\sqs\Queue::class,
            'url' => '<sqs url>',
            'key' => '<key>',
            'secret' => '<secret>',
            'region' => '<region>',
        ],
    ],
];
```

Configuration example for FIFO queues:

```php
return [
    'bootstrap' => [
        'queue', // The component registers own console commands
    ],
    'components' => [
        'queue' => [
            'class' => \yii\queue\sqs\Queue::class,
            'url' => '<sqs url>',
            'key' => '<key>',
            'secret' => '<secret>',
            'region' => '<region>',
            'messageGroupId' => '<Group ID>',
        ],
    ],
];
```

The message group ID is required by SQS for FIFO queues. You can configure your own or use the "default" value.

The deduplication ID is generated automatically, so no matter if you have activated content-based deduplication in the SQS queue or not, this ID will be used.

Console
-------

Console command is used to execute tasks.

```sh
yii queue/listen [timeout]
```

`listen` command launches a daemon which infinitely queries the queue. If there are new tasks
they're immediately obtained and executed. `timeout` parameter is number of seconds to wait a job.
It uses SQS "Long Polling" feature, that holds a connection between client and a queue. 

**Important:** `timeout` parameter for SQS driver must be in range between 0 and 20 seconds.

This method is most efficient when command is properly daemonized via
[supervisor](worker.md#supervisor) or [systemd](worker.md#systemd).

```sh
yii queue/run
```

`run` command obtains and executes tasks in a loop until queue is empty. Works well with
[cron](worker.md#cron).

`run` and `listen` commands have options:

- `--verbose`, `-v`: print executing statuses into console.
- `--isolate`: each task is executed in a separate child process.
- `--color`: highlighting for verbose mode.

```sh
yii queue/clear
```

`clear` command clears a queue.
