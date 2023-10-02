<?php

declare(strict_types = 1);

use yii\BaseYii;
use yii\queue\amqp_interop\Queue as AmqpInteropQueue;
use yii\queue\beanstalk\Queue as BeanstalkQueue;
use yii\queue\db\Queue as DbQueue;
use yii\queue\file\Queue as FileQueue;
use yii\queue\gearman\Queue as GearmanQueue;
use yii\queue\redis\Queue as RedisQueue;
use yii\queue\sqs\Queue as SqsQueue;
use yii\queue\stomp\Queue as StompQueue;
use yii\queue\sync\Queue as SyncQueue;

/**
 * Yii bootstrap file.
 * Used for enhanced IDE code autocompletion.
 * Note: To avoid "Multiple Implementations" PHPStorm warning and make autocomplete faster
 * exclude or "Mark as Plain Text" vendor/yiisoft/yii2/Yii.php file
 */
class Yii extends BaseYii
{
    /**
     * @var BaseApplication|WebApplication|ConsoleApplication the application instance
     */
    public static $app;
}

/**
 * Class BaseApplication
 * Used for properties that are identical for both WebApplication and ConsoleApplication
 *
 * @property AmqpInteropQueue $amqpInteropQueue
 * @property BeanstalkQueue $beanstalkQueue
 * @property FileQueue $fileQueue
 * @property GearmanQueue $gearmanQueue
 * @property RedisQueue $redisQueue
 * @property StompQueue $stompQueue
 * @property SyncQueue $syncQueue
 * @property SqsQueue $sqsQueue
 * @property SqsQueue $sqsFifoQueue
 * @property DbQueue $mysqlQueue
 * @property DbQueue $pgsqlQueue
 * @property DbQueue $sqliteQueue
 */
abstract class BaseApplication extends yii\base\Application
{
}

/**
 * Class WebApplication
 * Include only Web application related components here
 */
class WebApplication extends yii\web\Application
{
}

/**
 * Class ConsoleApplication
 * Include only Console application related components here
 */
class ConsoleApplication extends yii\console\Application
{
}
