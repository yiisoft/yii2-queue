DB 驱动
=========

DB 驱动使用库存储队列数据

配置示例:

```php
return [
    'bootstrap' => [
        'queue', // 把这个组件注册到控制台
    ],
    'components' => [
        'db' => [
            'class' => \yii\db\Connection::class, 
            // ...
        ],
        'queue' => [
            'class' => \yii\queue\db\Queue::class,
            'db' => 'db', // DB 连接组件或它的配置
            'tableName' => '{{%queue}}', // 表名
            'channel' => 'default', // Queue channel key
            'mutex' => \yii\mutex\MysqlMutex::class, // Mutex that used to sync queries
        ],
    ],
];
```

您必须向数据库添加一个表。MySQL示例语句:

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

迁移文件存放在 [src/drivers/db/migrations](../../src/drivers/db/migrations).

添加迁移到您的应用程序，编辑控制台配置文件以配置[命名空间迁移](http://www.yiiframework.com/doc-2.0/guide-db-migrations.html#namespaced-migrations):

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

然后使用迁移命令:

```sh
yii migrate/up
```

控制台
-------

控制台用于监听和处理队列任务。

```sh
yii queue/listen [timeout]
```

`listen` 命令启动一个守护进程，它可以无限查询队列。如果有新的任务，他们立即得到并执行。  
`timeout` 是下一次查询队列的时间 当命令正确地通过[supervisor](worker.md#supervisor)来实现时，这种方法是最有效的。

```sh
yii queue/run
```

`run` 命令获取并执行循环中的任务，直到队列为空。适用与[cron](worker.md#cron)。

`run` 与 `listen` 命令的参数:

- `--verbose`, `-v`: 详细模式执行作业。如果启用，将打印每个作业的执行结果。
- `--isolate`: 隔离模式。将在子进程中执行作业。
- `--color`: 在详细模式下高亮显示输出结果。

```sh
yii queue/info
```

`info` 命令打印关于队列状态的信息。

```sh
yii queue/clear
```

`clear` command clears a queue.

```sh
yii queue/remove [id]
```

`remove` command removes a job.
