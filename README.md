<p align="center">
    <picture>
        <source media="(prefers-color-scheme: dark)" srcset="https://www.yiiframework.com/image/yii_logo_dark.svg">
        <source media="(prefers-color-scheme: light)" srcset="https://www.yiiframework.com/image/yii_logo_light.svg">
        <img src="https://www.yiiframework.com/image/yii_logo_light.svg" alt="Yii Framework" height="100px">
    </picture>
    <h1 align="center">Yii2 Queue Extension</h1>
    <br>
</p>

An extension for running tasks asynchronously via queues.

It supports queues based on **DB**, **Redis**, **RabbitMQ**, **AMQP**, **Beanstalk**, **ActiveMQ** and **Gearman**.

For license information, see the [LICENSE](LICENSE.md) file.

[![Latest Stable Version](https://img.shields.io/packagist/v/yiisoft/yii2-queue.svg?style=for-the-badge&label=Stable&logo=packagist)](https://packagist.org/packages/yiisoft/yii2-queue)
[![Total Downloads](https://img.shields.io/packagist/dt/yiisoft/yii2-queue.svg?style=for-the-badge&label=Downloads)](https://packagist.org/packages/yiisoft/yii2-queue)
[![build](https://img.shields.io/github/actions/workflow/status/yiisoft/yii2-queue/main.yml?style=for-the-badge&logo=github&label=Build)](https://github.com/yiisoft/yii2-queue/actions?query=workflow%3Abuild)
[![codecov](https://img.shields.io/codecov/c/github/yiisoft/yii2-queue.svg?style=for-the-badge&logo=codecov&logoColor=white&label=Codecov)](https://codecov.io/gh/yiisoft/yii2-queue)
[![Static Analysis](https://img.shields.io/github/actions/workflow/status/yiisoft/yii2-queue/static.yml?style=for-the-badge&label=Static)](https://github.com/yiisoft/yii2-queue/actions/workflows/static.yml)

Installation
------------

> [!IMPORTANT]
> - The minimum required [PHP](https://www.php.net/) version is PHP `8.3`.

The preferred way to install this extension is through [composer](https://getcomposer.org/download/):

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

## Documentation

- [the guide](docs/guide/README.md)

## Support the project

[![Open Collective](https://img.shields.io/badge/Open%20Collective-sponsor-7eadf1?style=for-the-badge&logo=open%20collective&logoColor=7eadf1&labelColor=555555)](https://opencollective.com/yiisoft)

## Follow updates

[![Official website](https://img.shields.io/badge/Powered_by-Yii_Framework-green.svg?style=for-the-badge&logo=yii)](https://www.yiiframework.com/)
[![Follow on X](https://img.shields.io/badge/-Follow%20on%20X-1DA1F2.svg?style=for-the-badge&logo=x&logoColor=white&labelColor=000000)](https://x.com/yiiframework)
[![Telegram](https://img.shields.io/badge/telegram-join-1DA1F2?style=for-the-badge&logo=telegram)](https://t.me/yii_framework_in_english)
[![Slack](https://img.shields.io/badge/slack-join-1DA1F2?style=for-the-badge&logo=slack)](https://yiiframework.com/go/slack)
