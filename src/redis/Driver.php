<?php

namespace zhuravljov\yii\queue\redis;

use yii\base\BootstrapInterface;
use yii\di\Instance;
use yii\redis\Connection;
use zhuravljov\yii\queue\BaseDriver;

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
     * @return string
     */
    protected function getKey()
    {
        return $this->prefix . $this->queue->id;
    }

    /**
     * @inheritdoc
     */
    public function push($job)
    {
        $message = serialize($job);
        $this->redis->executeCommand('RPUSH', [$this->getKey(), $message]);
        return $message;
    }

    /**
     * @inheritdoc
     */
    public function pop(&$message, &$job)
    {
        $message = $this->redis->executeCommand('LPOP', [$this->getKey()]);
        if ($message !== null) {
            $job = unserialize($message);
            return true;
        } else {
            return false;
        }
    }

    /**
     * @inheritdoc
     */
    public function release($message)
    {
    }

    /**
     * @inheritdoc
     */
    public function purge()
    {
        $this->redis->executeCommand('DEL', [$this->getKey()]);
    }
}