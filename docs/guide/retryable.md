Errors and retryable jobs
=========================

The execution of a job can fail. This can be due to internal errors which result from
poorly written code which should be fixed first. But they can also fail due to external
problems like a service or a resource being unavailable. This can lead to Exceptions or
timeouts.

In the latter cases, it's good to be able to retry a job after some time. There are several ways to do this.

Retry options
-------------

The first method is to use component options:

```php
'components' => [
    'queue' => [
        'class' => \yii\queue\<driver>\Queue::class,
        'ttr' => 5 * 60, // Max time for job execution
        'attempts' => 3, // Max number of attempts
    ],
],
```

The `ttr` option sets the time to reserve for job execution. If the execution of a job takes longer than
this time, execution will be stopped and it will be returned to the queue for later retry. The same happens
if the job throws an Exception. The `attempts` option sets the max. number of attempts. If this number is
reached, and the job still isn't done, it will be removed from the queue as completed.

These options apply to all jobs in the queue. If you need to change this behavior for specific
jobs, see the following method.

RetryableJobInterface
---------------------

To have more control over the retry logic a job can implement the `RetryableJobInterface`.
For example:

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

The `getTtr()` and `canRetry()` methods have a higher priority than the component options mentioned above.

Event handlers
--------------

The third method to control TTR and number of retries for failed jobs involves the
`Queue::EVENT_BEFORE_PUSH` and `Queue::EVENT_AFTER_ERROR` events.

The TTR can be set with the `Queue::EVENT_BEFORE_PUSH` event:

```php
Yii::$app->queue->on(Queue::EVENT_BEFORE_PUSH, function (PushEvent $event) {
    if ($event->job instanceof SomeJob) {
        $event->ttr = 300;
    }
});
```

And the `Queue::EVENT_AFTER_ERROR` event can be used to decide whether to try another attempt:

```php
Yii::$app->queue->on(Queue::EVENT_AFTER_ERROR, function (ErrorEvent $event) {
    if ($event->job instanceof SomeJob) {
        $event->retry = ($event->attempt < 5) && ($event->error instanceof TemporaryException);
    }
});
```

Event handlers are executed after `RetryableJobInterface` methods, and therefore have the highest
priority.

Restrictions
------------

Full support for retryable jobs is implemented in the [Beanstalk], [DB], [File], [AMQP Interop] and [Redis] drivers.
The [Sync] driver will not retry failed jobs. The [Gearman] driver doesn't support retryable jobs.
[RabbitMQ] has only its basic retryable support, where the number of attempts can not be set.

[AWS SQS] uses [Dead Letter Queue] for handling messages that were failed to process.
All unprocessed messages after a maximum number of attempts are moved to that queue.
You should set an address of a Dead Letter Queue and a maximum number of attempts in the AWS Console while creating a queue.

[Beanstalk]: driver-beanstalk.md
[DB]: driver-db.md
[File]: driver-file.md
[Redis]: driver-redis.md
[Sync]: driver-sync.md
[Gearman]: driver-gearman.md
[RabbitMQ]: driver-amqp.md
[AMQP Interop]: driver-amqp-interop.md
[AWS SQS]: driver-sqs.md
[Dead Letter Queue]: https://docs.aws.amazon.com/AWSSimpleQueueService/latest/SQSDeveloperGuide/sqs-dead-letter-queues.html