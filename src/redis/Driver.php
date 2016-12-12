<?php

namespace zhuravljov\yii\queue\redis;

use yii\base\BootstrapInterface;
use yii\di\Instance;
use yii\helpers\Inflector;
use yii\redis\Connection;
use zhuravljov\yii\queue\Driver as BaseDriver;

/**
 * Redis Driver
 *
 * @author Roman Zhuravlev <zhuravljov@gmail.com>
 */
class Driver extends BaseDriver implements BootstrapInterface
{
    /**
     * @var Connection|array|string
     */
    public $redis = 'redis';
    /**
     * @var string
     */
    public $channel = 'queue';

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
        $this->redis = Instance::ensure($this->redis, Connection::class);
    }

    /**
     * @inheritdoc
     */
    public function bootstrap($app)
    {
        if ($app instanceof \yii\console\Application) {
            $app->controllerMap[Inflector::camel2id($this->queue->id)] = [
                'class' => Command::class,
                'driver' => $this,
            ];
        }
    }

    /**
     * @inheritdoc
     */
    public function push($job)
    {
        $this->redis->executeCommand('RPUSH', [$this->channel, $this->serialize($job)]);
    }

    /**
     * Runs all jobs from redis-queue.
     */
    public function run()
    {
        while (($message = $this->redis->executeCommand('LPOP', [$this->channel])) !== null) {
            $job = $this->unserialize($message);
            $this->getQueue()->run($job);
        }
    }

    /**
     * Listens redis-queue and runs new jobs.
     */
    public function listen()
    {
        while (true) {
            list(, $message) = $this->redis->executeCommand('BLPOP', [$this->channel, 0]);
            $job = $this->unserialize($message);
            $this->getQueue()->run($job);
        }
    }
}