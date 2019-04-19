RabbitMQ 驱动
===============

**Note:** The driver has been deprecated since 2.0.2 and will be removed in 2.1.
Consider using [amqp_interop](driver-amqp-interop.md) driver instead.

这个驱动使用 RabbitMQ 队列.

如果要使用这个驱动，你应该在你的项目中加入 `php-amqplib/php-amqplib`。

配置示例:

```php
return [
    'bootstrap' => [
        'queue', // 把这个组件注册到控制台
    ],
    'components' => [
        'queue' => [
            'class' => \yii\queue\amqp\Queue::class,
            'host' => 'localhost',
            'port' => 5672,
            'user' => 'guest',
            'password' => 'guest',
            'queueName' => 'queue',
        ],
    ],
];
```

控制台
-------

控制台用于监听和处理队列任务。

```sh
yii queue/listen
```

`listen` 命令启动一个守护进程，它可以无限查询队列。如果有新任务的话它们立即得到并执行。当命令正确地通过[supervisor](worker.md#supervisor)来实现时，这种方法是最有效 。

`listen` 命令参数:

- `--verbose`, `-v`: 详细模式执行作业。如果启用，将打印每个作业的执行结果。
- `--isolate`: 隔离模式。将在子进程中执行作业。
- `--color`: 在详细模式下高亮显示输出结果。
