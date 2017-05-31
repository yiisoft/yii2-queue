Usage basics
============


Configuration
-------------

In order to use extension you have to configure it like the following:

```php
return [
    'bootstrap' => [
        'queue', // The component registers own console commands
    ],
    'components' => [
        'queue' => [
            'class' => \zhuravljov\yii\queue\<driver>\Queue::class,
            'as log' => \zhuravljov\yii\queue\LogBehavior::class,
            // Other driver options
        ],
    ],
];
```

A list of drivers available and their configuration docs is available in [table of contents](README.md).


Usage in code
-------------

Each task which is sent to queue should be defined as a separate class.
For example, if you need to download and save a file the class may look like the following:

```php
class DownloadJob extends Object implements \zhuravljov\yii\queue\Job
{
    public $url;
    public $file;
    
    public function execute($queue)
    {
        file_put_contents($this->file, file_get_contents($this->url));
    }
}
```

Here's how to send a task into queue:

```php
Yii::$app->queue->push(new DownloadJob([
    'url' => 'http://example.com/image.jpg',
    'file' => '/tmp/image.jpg',
]));
```
Pushes job into queue that run after 5 min:

```php
Yii::$app->queue->delay(5 * 60)->push(new DownloadJob([
    'url' => 'http://example.com/image.jpg',
    'file' => '/tmp/image.jpg',
]));
```

**Important:** only some drivers support delayed running.


Queue handling
--------------

The exact way task is executed depends on the driver used. The most part of drivers can be run using
console commands, which the component registers in your application. For more details see documentation
of a driver.


Job status
----------

The component has ability to track status of a job which was pushed into queue.

```php
// Push a job into queue and get massage ID.
$id = Yii::$app->queue->push(new SomeJob());

// The job is waiting for execute.
Yii::$app->queue->isWaiting($id);

// Worker gets the job from queue, and executing it.
Yii::$app->queue->isReserved($id);

// Worker has executed the job.
Yii::$app->queue->isDone($id);
```

**Important:** RabbitMQ driver doesn't support job statuses.


Messaging third party workers
-----------------------------

You may pass any data to queue:

```php
Yii::$app->queue->push([
    'function' => 'download',
    'url' => 'http://example.com/image.jpg',
    'file' => '/tmp/image.jpg',
]);
```

This is useful if the queue is processed using a specially developer third party worker.

If worker is implemented using something other than PHP you have to change the way data is serialized. For example,
to JSON:

```php
return [
    'components' => [
        'queue' => [
            'class' => \zhuravljov\yii\queue\<driver>\Queue::class,
            'serializer' => \zhuravljov\yii\queue\serializers\JsonSerializer::class,
        ],
    ],
];
```

Handling events
---------------

Queue triggers the following events:

| Event name                      | Event class  | Triggered on                                              |
|---------------------------------|--------------|-----------------------------------------------------------|
| [Queue::EVENT_BEFORE_PUSH]      | [PushEvent]  | Adding job to queue using `Queue::push()` method          |
| [Queue::EVENT_AFTER_PUSH]       | [PushEvent]  | Adding job to queue using `Queue::push()` method          |
| [Queue::EVENT_BEFORE_EXEC]      | [JobEvent]   | Before each job execution                                 |
| [Queue::EVENT_AFTER_EXEC]       | [JobEvent]   | After each success job execution                          |
| [Queue::EVENT_AFTER_EXEC_ERROR] | [ErrorEvent] | When uncaught exception occurred during the job execution |

[Queue::EVENT_BEFORE_PUSH]: https://github.com/zhuravljov/yii2-queue/blob/1.0.0/src/Queue.php#L27
[Queue::EVENT_AFTER_PUSH]: https://github.com/zhuravljov/yii2-queue/blob/1.0.0/src/Queue.php#L31
[Queue::EVENT_BEFORE_EXEC]: https://github.com/zhuravljov/yii2-queue/blob/1.0.0/src/Queue.php#L35
[Queue::EVENT_AFTER_EXEC]: https://github.com/zhuravljov/yii2-queue/blob/1.0.0/src/Queue.php#L39
[Queue::EVENT_AFTER_EXEC_ERROR]: https://github.com/zhuravljov/yii2-queue/blob/1.0.0/src/Queue.php#L43
[PushEvent]: https://github.com/zhuravljov/yii2-queue/blob/1.0.0/src/PushEvent.php
[JobEvent]: https://github.com/zhuravljov/yii2-queue/blob/1.0.0/src/JobEvent.php
[ErrorEvent]: https://github.com/zhuravljov/yii2-queue/blob/1.0.0/src/ErrorEvent.php

You can easily attach your own handler to any of these events.
For example, let's delay the job, if its execution failed with a special exception: 

```php
Yii::$app->queue->on(Queue::EVENT_AFTER_EXEC_ERROR, function ($event) {
    if ($event->error instanceof TemporaryUnprocessableJobException) {
        $queue = $event->sender;
        $queue->delay(7200)->push($event->job);    
    }
});
```

Logging events
--------------

This component provides the [LogBehavior](https://github.com/zhuravljov/yii2-queue/blob/1.0.0/src/LogBehavior.php) 
to log Queue events using [Yii built-in Logger](http://www.yiiframework.com/doc-2.0/guide-runtime-logging.html).

To use it, simply configure the Queue component as follows:

```php
return [
    'components' => [
        'queue' => [
            'class' => \zhuravljov\yii\queue\redis\Queue::class,
            'as log' => \zhuravljov\yii\queue\LogBehavior::class
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
        'queue1', // First component registers own console commands
        'queue2', // Second component registers own console commands
    ],
    'components' => [
        'queue1' => [
            'class' => \zhuravljov\yii\queue\redis\Queue::class,
        ],
        'queue2' => [
            'class' => \zhuravljov\yii\queue\db\Queue::class,
            'serializer' => \zhuravljov\yii\queue\serializers\JsonSerializer::class,
        ],
    ],
];
```

Usage example:

```php
// Sending task to queue to be processed via standard worker
Yii::$app->queue1->push(new DownloadJob([
    'url' => 'http://example.com/image.jpg',
    'file' => '/tmp/image.jpg',
]));

// Sending tasks to another queue to be processed by third party worker
Yii::$app->queue2->push([
    'function' => 'download',
    'url' => 'http://example.com/image.jpg',
    'file' => '/tmp/image.jpg',
]);
```


Limitations
-----------

When using queues it's important to remember that tasks are put into queue and are obtained from queue in separate
processes. Therefore avoid external dependencies when executing a task if you're not sure if they are available in
the environment where it worker does its job.

All the data to process the task should be put into properties of your job object and sent into queue along with it.

If you need to process `ActiveRecord` then send its ID instead of the object itself. When processing you have to extract
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
class SomeJob extends Object implements \zhuravljov\yii\queue\Job
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
