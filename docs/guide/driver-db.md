DB Driver
=========

DB driver uses database to store queue data.

Configuration example:

```php
return [
    'bootstrap' => [
        'queue', // The component registers own console commands
    ],
    'components' => [
        'db' => [
            'class' => \yii\db\Connection::class, 
            // ...
        ],
        'queue' => [
            'class' => \yii\queue\db\Queue::class,
            'db' => 'db', // DB connection component or its config 
            'tableName' => '{{%queue}}', // Table name
            'channel' => 'default', // Queue channel key
            'mutex' => \yii\mutex\MysqlMutex::class, // Mutex that used to sync queries
        ],
    ],
];
```


You have to add a table to the database. Example schema for MySQL:

```SQL
CREATE TABLE `queue` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `channel` varchar(255) NOT NULL,
  `job` blob NOT NULL,
  `pushed_at` int(11) NOT NULL,
  `ttr` int(11) NOT NULL,
  `delay` int(11) NOT NULL DEFAULT 0,
  `priority` int(11) unsigned NOT NULL DEFAULT 1024,
  `reserved_at` int(11) DEFAULT NULL,
  `attempt` int(11) DEFAULT NULL,
  `done_at` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `channel` (`channel`),
  KEY `reserved_at` (`reserved_at`),
  KEY `priority` (`priority`)
) ENGINE=InnoDB
```

Migrations are available from [src/drivers/db/migrations](../../src/drivers/db/migrations).

```sh
./yii migrate/up --migrationPath=@vendor/yiisoft/yii2-queue/src/drivers/db/migrations
```


Console
-------

Console command is used to execute tasks.

```sh
yii queue/listen [delay]
```

`listen` command launches a daemon which infinitely queries the queue. If there are new tasks
they're immediately obtained and executed. `delay` is time in seconds to wait between querying
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
