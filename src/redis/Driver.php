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
    public $prefix = '';

    /**
     * @inheritdoc
     */
    public function push($channel, $job)
    {
        $key = $this->getKey($channel);
        $message = serialize($job);
        $this->redis->executeCommand('RPUSH', [$key, $message]);
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
    public function init()
    {
        parent::init();
        $this->redis = Instance::ensure($this->redis, Connection::class);
    }

    public function run($channel)
    {
        while (($message = $this->pop($channel)) !== null) {
            $job = unserialize($message);
            $this->getQueue()->run($channel, $job);
        }
    }

    protected function pop($channel)
    {
        return $this->redis->executeCommand('LPOP', [$this->getKey($channel)]);
    }

    /**
     * @param string $channel
     * @return string
     */
    protected function getKey($channel)
    {
        return $this->prefix . $this->queue->id . ':' . $channel;
    }
}