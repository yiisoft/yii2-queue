<p align="center">
    <a href="https://github.com/yiisoft" target="_blank">
        <img src="https://avatars0.githubusercontent.com/u/993323" height="100px">
    </a>
    <h1 align="center">Yii2 Queue Extension</h1>
    <br>
</p>

An extension for running tasks asynchronously via queues.

It supports queues based on **DB**, **Redis**, **RabbitMQ**, **AMQP**, **Beanstalk**, **ActiveMQ** and **Gearman**.

Documentation is at [docs/guide/README.md](docs/guide/README.md).

[![Latest Stable Version](https://poser.pugx.org/yiisoft/yii2-queue/v/stable.svg)](https://packagist.org/packages/yiisoft/yii2-queue)
[![Total Downloads](https://poser.pugx.org/yiisoft/yii2-queue/downloads.svg)](https://packagist.org/packages/yiisoft/yii2-queue)
[![Build Status](https://github.com/yiisoft/yii2-queue/workflows/build/badge.svg)](https://github.com/yiisoft/yii2-queue/actions)

Installation
------------

The preferred way to install this extension is through [composer](http://getcomposer.org/download/):

```
php composer.phar require --prefer-dist yiisoft/yii2-queue
```

Basic Usage
-----------

Each task which is sent to queue should be defined as a separate class.
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

Here's how to send a task into the queue:

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

The exact way a task is executed depends on the used driver. Most drivers can be run using
console commands, which the component automatically registers in your application.

This command obtains and executes tasks in a loop until the queue is empty:

```sh
yii queue/run
```

This command launches a daemon which infinitely queries the queue:

```sh
yii queue/listen
```

See the documentation for more details about driver specific console commands and their options.

The component also has the ability to track the status of a job which was pushed into queue.

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

For more details see [the guide](docs/guide/README.md).
