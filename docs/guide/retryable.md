Errors and retryable jobs
=========================

Exceptions can be thrown during job handling. This can be internal errors which result of poorly
written code, and external, when the requested services and external resources are unavailable.
In the second case, it's good to be able to retry a job after time.

There are several ways to do this.

Retry options
-------------

The first method is implemented by the component options:

```php
'components' => [
    'queue' => [
        'class' => \zhuravljov\yii\queue\<driver>\Queue::class,
        'ttr' => 5 * 60, // Max time for anything job handling 
        'attempts' => 3, // Max number of attempts
    ],
],
```

The `ttr` option sets time to reserve of a job in queue. If a job doesn't executed during this time,
it will return to a queue for retry. The `attempts` option sets max number of attempts. If attempts
are over, and the job isn't done, it will be removed from a queue as completed.

The options extend to all jobs in a queue, and if you need to change this behavior fo several jobs,
there is second method.
 
RetryableJob interface
----------------------

Separate control of retry is implemented by `RetryableJob` interface. For example:

```php
class SomeJob extends Object implements RetryableJob
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

`getTtr()` and `canRetry()` methods have a higher priority than the component options.

Event handlers
--------------

The third method to set TTR and the need to retry of failed job involves using
`Queue::EVENT_BEFORE_PUSH` and `Queue::EVENT_AFTER_ERROR` events.

`Queue::EVENT_BEFORE_PUSH` event can be used to set TTR:

```php
Yii::$app->queue->on(Queue::EVENT_BEFORE_PUSH, function (PushEvent $event) {
    if ($event->job instanceof SomeJob) {
        $event->ttr = 300;
    }
});
```

And `Queue::EVENT_AFTER_ERROR` event can be used to set a new attempt:

```php
Yii::$app->queue->on(Queue::EVENT_AFTER_ERROR, function (ErrorEvent $event) {
    if ($event->job instanceof SomeJob) {
        $event->retry = ($event->attempt < 5) && ($event->error instanceof TemporaryException);
    }
});
```

Event handlers are executed after `RetryableJob` methods, and therefore have the highest priority.

Restrictions
------------

Full support of retryable implements for Beanstalk, DB, File and Redis drivers. Sync driver will not
retry failed jobs. Gearman driver doesn't support of retryable. RabbitMQ has only its basic
retryable support, in which an attempt number can not be got.
