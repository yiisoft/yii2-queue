DB Driver
=========

DB driver uses database to store queue data.

Configuration example:

```php
return [
    'bootstrap' => ['queue'],
    'components' => [
        'queue' => [
            'class' => \zhuravljov\yii\queue\db\Queue::class,
            'db' => 'db', // connection ID
            'tableName' => '{{%queue}}', // table
            'channel' => 'default', // queue channel
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
  `created_at` int(11) NOT NULL,
  `timeout` int(11) NOT NULL,
  `started_at` int(11) DEFAULT NULL,
  `finished_at` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `channel` (`channel`),
  KEY `started_at` (`started_at`)
) ENGINE=InnoDB
```

Migrations are available from [src/db/migrations](../../src/db/migrations).

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

```bash
yii queue/stat
```

`stat` command prints out statistics.
