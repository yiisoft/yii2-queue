错误与重复执行
=========================

在作业处理期间可以抛出异常。 当请求的服务和外部资源不可用时，由于代码编写的比较糟糕而导致的内部错误。  
在第二种情况下，可以在一段时间后重新尝试这个作业。

有几种方法可以做到这一点。

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

 `ttr` 选项设置了在队列中保留工作的时间。如果一份作业在这段时间没有执行，它将返回队列进行重试。  
 `attempts` 选项设置了最大的尝试次数。如果尝试已经结束，作业作还没有完成，它将从队列中移除。

这种将全局设置队列中的所有作业，如果您需要为多个作业进行不同的设置可以使用，
第二种方法。
 
重试作业接口
----------------------

Separate control of retry is implemented by `RetryableJobInterface` 接口。 示例:

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

完全支持 [Beanstalk], [DB], [File] 和 [Redis] 驱动程序的重试工具。
[Sync] 驱动不会重试失败的工作 [Gearman] 不支持重试。
[RabbitMQ] 基本版支持，但重试编号无法得到。

[Beanstalk]: driver-beanstalk.md
[DB]: driver-db.md
[File]: driver-file.md
[Redis]: driver-redis.md
[Sync]: driver-sync.md
[Gearman]: driver-gearman.md
[RabbitMQ]: driver-amqp.md