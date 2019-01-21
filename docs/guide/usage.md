Usage basics
============


Configuration
-------------

In order to use the extension you have to configure it like the following:

```php
return [
    'bootstrap' => [
        'queue', // The component registers its own console commands
    ],
    'components' => [
        'queue' => [
            'class' => \yii\queue\<driver>\Queue::class,
            'as log' => \yii\queue\LogBehavior::class,
            // Other driver options
        ],
    ],
];
```

A list of available drivers and their docs is available in the [table of contents](README.md).


Usage
-----

Each task which is sent to the queue should be defined as a separate class.
For example, if you need to download and save a file the class may look like the following:

```php
class DownloadJob extends BaseObject implements \yii\queue\JobInterface
{
    public $url;
    public $file;
    
    public function execute($queue)
    {
        file_put_contents($this->file, file_get_contents($this->url));
    }
}
```

Here's how to send a task to the queue:

```php
Yii::$app->queue->push(new DownloadJob([
    'url' => 'http://example.com/image.jpg',
    'file' => '/tmp/image.jpg',
]));
```
To push a job into the queue that should run after 5 minutes:

```php
Yii::$app->queue->delay(5 * 60)->push(new DownloadJob([
    'url' => 'http://example.com/image.jpg',
    'file' => '/tmp/image.jpg',
]));
```
**Important:** Not all drivers support delayed running.

You can also specify a priority when pushing a job:

```php
Yii::$app->queue->priority(10)->push(new ErrorNotification([
    'recipient' => 'notifyme@example.com',
]));
```

Jobs with a lower priority will be executed first. The default priority is `1024`.

**Important:** Not all drivers support job priorities.


Queue handling
--------------

The exact way how a task is executed depends on the driver being used. Most drivers can be run using
console commands, which the component registers in your application. For more details check the respective
driver documentation.


Job status
----------

The component can track the status of a job that was pushed into the queue.

```php
// Push a job into the queue and get a message ID.
$id = Yii::$app->queue->push(new SomeJob());

// Check whether the job is waiting for execution.
Yii::$app->queue->isWaiting($id);

// Check whether a worker got the job from the queue and executes it.
Yii::$app->queue->isReserved($id);

// Check whether a worker has executed the job.
Yii::$app->queue->isDone($id);
```

**Important:** The RabbitMQ and AWS SQS drivers don't support job statuses.


Messaging third party workers
-----------------------------

You may pass any data to the queue:

```php
Yii::$app->queue->push([
    'function' => 'download',
    'url' => 'http://example.com/image.jpg',
    'file' => '/tmp/image.jpg',
]);
```

This is useful if the queue is processed using a custom third party worker.

If the worker is not implemented in PHP you have to change the way data is serialized.
For example to serialize to JSON:

```php
return [
    'components' => [
        'queue' => [
            'class' => \yii\queue\<driver>\Queue::class,
            'strictJobType' => false,
            'serializer' => \yii\queue\serializers\JsonSerializer::class,
        ],
    ],
];
```

Handling events
---------------

The queue triggers the following events:

| Event name                   | Event class | Triggered                                                 |
|------------------------------|-------------|-----------------------------------------------------------|
| Queue::EVENT_BEFORE_PUSH     | PushEvent   | before adding a job to queue using `Queue::push()` method |
| Queue::EVENT_AFTER_PUSH      | PushEvent   | after adding a job to queue using `Queue::push()` method  |
| Queue::EVENT_BEFORE_EXEC     | ExecEvent   | before executing a job                                    |
| Queue::EVENT_AFTER_EXEC      | ExecEvent   | after successful job execution                            |
| Queue::EVENT_AFTER_ERROR     | ExecEvent   | on uncaught exception during the job execution            |
| cli\Queue:EVENT_WORKER_START | WorkerEvent | when worker has been started                              |
| cli\Queue:EVENT_WORKER_LOOP  | WorkerEvent | on each iteration between requests to queue               |
| cli\Queue:EVENT_WORKER_STOP  | WorkerEvent | when worker has been stopped                              |

You can easily attach your own handler to any of these events.
For example, let's delay the job, if its execution failed with a special exception:

```php
Yii::$app->queue->on(Queue::EVENT_AFTER_ERROR, function ($event) {
    if ($event->error instanceof TemporaryUnprocessableJobException) {
        $queue = $event->sender;
        $queue->delay(7200)->push($event->job);
    }
});
```

Logging events
--------------

The component provides the `LogBehavior` to log Queue events using
[Yii's built-in Logger](http://www.yiiframework.com/doc-2.0/guide-runtime-logging.html).

To enable it, simply configure the queue component as follows:

```php
return [
    'components' => [
        'queue' => [
            'class' => \yii\queue\redis\Queue::class,
            'as log' => \yii\queue\LogBehavior::class
        ],
    ],
];
```


Multiple queues
---------------

Configuration example:

```php
return [
    'bootstrap' => [
        'queue1', // First component registers its own console commands
        'queue2', // Second component registers its own console commands
    ],
    'components' => [
        'queue1' => [
            'class' => \yii\queue\redis\Queue::class,
        ],
        'queue2' => [
            'class' => \yii\queue\db\Queue::class,
            'strictJobType' => false,
            'serializer' => \yii\queue\serializers\JsonSerializer::class,
        ],
    ],
];
```

Usage example:

```php
// Sending a task to the queue to be processed via standard worker
Yii::$app->queue1->push(new DownloadJob([
    'url' => 'http://example.com/image.jpg',
    'file' => '/tmp/image.jpg',
]));

// Sending a task to another queue to be processed by a third party worker
Yii::$app->queue2->push([
    'function' => 'download',
    'url' => 'http://example.com/image.jpg',
    'file' => '/tmp/image.jpg',
]);
```


Limitations
-----------

When using queues it's important to remember that tasks are put into and obtained from the queue in separate
processes. Therefore avoid external dependencies when executing a task if you're not sure if they are available in
the environment where the worker does its job.

All the data to process the task should be put into properties of your job object and be sent into the queue along with it.

If you need to process an `ActiveRecord` then send its ID instead of the object itself. When processing you have to extract
it from DB.

For example:

```php
Yii::$app->queue->push(new SomeJob([
    'userId' => Yii::$app->user->id,
    'bookId' => $book->id,
    'someUrl' => Url::to(['controller/action']),
]));
```

Task class:

```php
class SomeJob extends BaseObject implements \yii\queue\JobInterface
{
    public $userId;
    public $bookId;
    public $someUrl;

    public function execute($queue)
    {
        $user = User::findOne($this->userId);
        $book = Book::findOne($this->bookId);
        //...
    }
}
```
