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
                'queue' => $this->queue,
            ];
        }
    }

    /**
     * @param string $channel
     * @return string
     */
    protected function getKey($channel)
    {
        return $this->prefix . $this->queue->id . ':' . $channel;
    }

    /**
     * @inheritdoc
     */
    public function push($channel, $job)
    {
        $message = serialize($job);
        $this->redis->executeCommand('RPUSH', [$this->getKey($channel), $message]);
        return $message;
    }

    /**
     * @inheritdoc
     */
    public function work($channel, $handler)
    {
        $count = 0;
        while (($message = $this->redis->executeCommand('LPOP', [$this->getKey($channel)])) !== null) {
            $count++;
            $job = unserialize($message);
            call_user_func($handler, $job);
        }
        return $count;
    }

    /**
     * @inheritdoc
     */
    public function purge($channel)
    {
        $this->redis->executeCommand('DEL', [$this->getKey($channel)]);
    }
}