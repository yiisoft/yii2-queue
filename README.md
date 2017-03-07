Yii2 Queue Extension
====================

Extension for async execution of jobs through a queue mechanism.

It supported queues based on **DB**, **Redis**, **RabbitMQ**, **Beanstalk** and **Gearman**.

Documentation is at [docs/guide/README.md](docs/guide/README.md).

[![Latest Stable Version](https://poser.pugx.org/zhuravljov/yii2-queue/v/stable.svg)](https://packagist.org/packages/zhuravljov/yii2-queue)
[![Total Downloads](https://poser.pugx.org/zhuravljov/yii2-queue/downloads.svg)](https://packagist.org/packages/zhuravljov/yii2-queue)
[![Build Status](https://travis-ci.org/zhuravljov/yii2-queue.svg)](https://travis-ci.org/zhuravljov/yii2-queue)

Installation
------------

The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

Either run

```
php composer.phar require --prefer-dist zhuravljov/yii2-queue
```

or add

```
"zhuravljov/yii2-queue": "*"
```

to the require section of your `composer.json` file.

Basic Usage
-----------

Job class example:

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

Pushes job into queue:

```php
Yii::$app->queue->push(new DownloadJob([
    'url' => 'http://example.com/image.jpg',
    'file' => '/tmp/image.jpg',
]));
```

Method of handling a queue depend on selected driver.

Pushes job into queue that run after 5 min:

```php
Yii::$app->queue->later(new DownloadJob([
    'url' => 'http://example.com/image.jpg',
    'file' => '/tmp/image.jpg',
]), 5 * 60);
```

But only some drivers support delayed running.

For more details see [the guide](docs/guide/README.md).
