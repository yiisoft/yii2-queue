<?php

namespace zhuravljov\yii\queue\redis;

use yii\base\BootstrapInterface;
use yii\di\Instance;
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
            $app->controllerMap[$this->queue->id] = [
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
        $message = serialize($job);
        $this->redis->executeCommand('RPUSH', [$this->channel, $message]);
    }

    public function run()
    {
        while (($message = $this->pop()) !== null) {
            $job = unserialize($message);
            $this->getQueue()->run($job);
        }
    }

    protected function pop()
    {
        return $this->redis->executeCommand('LPOP', [$this->channel]);
    }
}