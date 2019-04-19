DB Driver
=========

The DB driver uses a database to store queue data.

Configuration example:

```php
return [
    'bootstrap' => [
        'queue', // The component registers its own console commands
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
            'mutex' => \yii\mutex\MysqlMutex::class, // Mutex used to sync queries
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

To add migrations to your application, edit the console config file to configure
[a namespaced migration](http://www.yiiframework.com/doc-2.0/guide-db-migrations.html#namespaced-migrations):

```php
'controllerMap' => [
    // ...
    'migrate' => [
        'class' => 'yii\console\controllers\MigrateController',
        'migrationPath' => null,
        'migrationNamespaces' => [
            // ...
            'yii\queue\db\migrations',
        ],
    ],
],
```

Then issue the `migrate/up` command:

```sh
yii migrate/up
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

- `--verbose`, `-v`: verbose mode of a job execution. If enabled, the execution results of each job will be printed.
- `--isolate`: isolate mode. It executes a job in a child process.
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
