Beanstalk 驱动
================

驱动程序使用 Beanstalk 队列。

配置示例:

```php
return [
    'bootstrap' => [
        'queue', // 把这个组件注册到控制台
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
yii queue/remove [id]
```

`remove` command removes a job.
