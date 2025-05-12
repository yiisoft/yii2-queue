错误与重复执行
=========================

作业的执行可能会失败。这可能是由于内部错误造成的，这些错误是由编写不当的代码导致的，应该首先修复。但是，它们也可能由于外部问题（例如服务或资源不可用）而失败。这可能会导致异常或超时。  
在后一种情况下，最好能够在一段时间后重试作业。有几种方法可以做到这一点。

>注意：下面描述的 ttr 功能需要安装 [PHP Process Control （pcntl）](https://www.php.net/manual/en/book.pcntl.php) 扩展，并且 worker 命令必须使用 --isolate 选项（默认启用）。

重试选项
-------------

第一个方法由组件选项实现:

```php
'components' => [
    'queue' => [
        'class' => \yii\queue\<driver>\Queue::class,
        'ttr' => 5 * 60, // Max time for anything job handling 
        'attempts' => 3, // Max number of attempts
    ],
],
```

 `ttr` 选项设置了作业必须成功完成的秒数。因此，可能会发生两件事来使工作失败:
 1. 作业在`ttr`结束之前发生异常
 2. 完成作业所需的时间将比 ttr 长（超时），工作线程停止作业执行
    
在这两种情况下，作业都将发送回队列进行重试。但请注意，在第一种情况下，即使作业在运行后立即停止，也不会立即重试，要等到`ttr`"用完"。也就是说，在将作业发送回队列之前，必须经过剩余的 ttr 秒数。

 `attempts` 选项设置了最大的尝试次数。如果尝试已经结束，作业作还没有完成，它将从队列中移除。

这种将全局设置队列中的所有作业，如果您需要为多个作业进行不同的设置可以使用，
第二种方法。
 
重试作业接口
----------------------

为了更好地控制重试逻辑，作业可以实现  `RetryableJobInterface` 接口。 示例:

```php
class SomeJob extends BaseObject implements RetryableJobInterface
{
    public function execute($queue)
    {
        //...
    }

    public function getTtr()
    {
        return 15 * 60;
    }

    public function canRetry($attempt, $error)
    {
        return ($attempt < 5) && ($error instanceof TemporaryException);
    }
}
```

`getTtr()` 与 `canRetry()` 方法比组件配置有更高的优先级。

事件处理
--------------

第三种方法设置TTR和需要重试失败的作业包括使用
`Queue::EVENT_BEFORE_PUSH` 与 `Queue::EVENT_AFTER_ERROR` 事件。

`Queue::EVENT_BEFORE_PUSH` 事件可用于设置TTR:

```php
Yii::$app->queue->on(Queue::EVENT_BEFORE_PUSH, function (PushEvent $event) {
    if ($event->job instanceof SomeJob) {
        $event->ttr = 300;
    }
});
```

并且 `Queue::EVENT_AFTER_ERROR` 事件可用于设置新的尝试:

```php
Yii::$app->queue->on(Queue::EVENT_AFTER_ERROR, function (ExecEvent $event) {
    if ($event->job instanceof SomeJob) {
        $event->retry = ($event->attempt < 5) && ($event->error instanceof TemporaryException);
    }
});
```

事件处理程序在 `RetryableJobInterface` 方法之后执行，因此具有最高优先级。

限制
------------

[Beanstalk], [DB], [File] 和 [Redis] 驱动程序实现了对可重试作业的完全支持。
[Sync] 驱动不会重试失败的作业 [Gearman] 不支持重试作业。
[RabbitMQ] 只有其基本的可重试支持，其中无法设置尝试次数。

[Beanstalk]: driver-beanstalk.md
[DB]: driver-db.md
[File]: driver-file.md
[Redis]: driver-redis.md
[Sync]: driver-sync.md
[Gearman]: driver-gearman.md
[RabbitMQ]: driver-amqp.md
