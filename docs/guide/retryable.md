Errors and retryable jobs
=========================

The execution of a job can fail. This can be due to internal errors which result from
poorly written code which should be fixed first. But they can also fail due to external
problems like a service or a resource being unavailable. This can lead to exceptions or
timeouts.

In the latter cases, it's good to be able to retry a job after some time. There are several ways to do this.

> **Note:** The `ttr` feature described below requires the
> [PHP Process Control (pcntl) extension](http://php.net/manual/en/book.pcntl.php) to be installed
> and the worker command has to use the `--isolate` option (which is enabled by default).

Retry options
-------------

The first method is to use queue component options:

```php
'components' => [
    'queue' => [
        'class' => \yii\queue\<driver>\Queue::class,
        'ttr' => 5 * 60, // Max time for job execution
        'attempts' => 3, // Max number of attempts
    ],
],
```

The `ttr` (Time to reserve, TTR) option defines the number of seconds during which a job must
be successfully completed. So two things can happen to make a job fail:

 1. The job throws an exception before `ttr` is over
 2. It would take longer than `ttr` to complete the job (timeout) and thus the
 job execution is stopped by the worker.

In both cases, the job will be sent back to the queue for a retry. Note though,
that in the first case the `ttr` is still "used up" even if the job stops right
after it has stared. I.e. the remaining seconds of `ttr` have to pass before the
job is sent back to the queue.

The `attempts` option sets the max. number of attempts. If this number is
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
Yii::$app->queue->on(Queue::EVENT_AFTER_ERROR, function (ExecEvent $event) {
    if ($event->job instanceof SomeJob) {
        $event->retry = ($event->attempt < 5) && ($event->error instanceof TemporaryException);
    }
});
```

Event handlers are executed after the `RetryableJobInterface` methods, and therefore have the highest
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