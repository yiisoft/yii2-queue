Usage basics
============


Configuration
-------------

In order to use extension you have to configure it like the following:

```php
return [
    'bootstrap' => ['queue'],
    'components' => [
        'queue' => [
            'class' => \zhuravljov\yii\queue\Queue::class,
            'as log' => \zhuravljov\yii\queue\LogBehavior::class,
            'driver' => [], // driver config
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
    
    public function run()
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

The exact way task is executed depends on the driver used.


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
            'class' => \zhuravljov\yii\queue\Queue::class,
            'driver' => [
                'serializer' => 'json_encode',
            ],
        ],
    ],
];
```


Multiple queues
---------------

Configuration example:

```php
return [
    'components' => [
        'queue1' => [
            'class' => \zhuravljov\yii\queue\Queue::class,
            'driver' => \zhuravljov\yii\queue\redis\Driver::class,
        ],
        'queue2' => [
            'class' => \zhuravljov\yii\queue\Queue::class,
            'driver' => [
                'class' => \zhuravljov\yii\queue\db\Driver::class,
                'serializer' => 'json_encode',
            ],
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
    
    public function run()
    {
        $user = User::findOne($this->userId);
        $book = Book::findOne($this->bookId);
        //...
    }
}
```
