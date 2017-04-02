<?php
/**
 * @link https://github.com/zhuravljov/yii2-queue
 * @copyright Copyright (c) 2017 Roman Zhuravlev
 * @license http://opensource.org/licenses/BSD-3-Clause
 */

namespace zhuravljov\yii\queue\redis;

use yii\base\BootstrapInterface;
use yii\console\Application as ConsoleApp;
use yii\di\Instance;
use yii\redis\Connection;
use zhuravljov\yii\queue\Queue as BaseQueue;
use zhuravljov\yii\queue\Signal;

/**
 * Redis Queue
 *
 * @author Roman Zhuravlev <zhuravljov@gmail.com>
 */
class Queue extends BaseQueue implements BootstrapInterface
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
        if ($app instanceof ConsoleApp) {
            $app->controllerMap[$this->getId()] = [
                'class' => Command::class,
                'queue' => $this,
            ];
        }
    }

    /**
     * Runs all jobs from redis-queue.
     */
    public function run()
    {
        $this->openWorker();
        while (($payload = $this->pop(0)) !== null) {
            list($id, $message) = explode(':', $payload, 2);
            $this->handleMessage($message);
        }
        $this->closeWorker();
    }

    /**
     * Listens redis-queue and runs new jobs.
     */
    public function listen()
    {
        $this->openWorker();
        while (!Signal::isExit()) {
            if (($payload = $this->pop(3)) !== null) {
                list($id, $message) = explode(':', $payload, 2);
                $this->handleMessage($message);
            }
        }
        $this->closeWorker();
    }

    /**
     * @param int $wait timeout
     * @return string|null payload
     */
    protected function pop($wait)
    {
        // Move delayed messages into reserved
        if ($this->now < time()) {
            $this->now = time();
            if ($delayed = $this->redis->zrevrangebyscore("$this->channel.delayed", $this->now, '-inf')) {
                $this->redis->zremrangebyscore("$this->channel.delayed", '-inf', $this->now);
                foreach ($delayed as $payload) {
                    $this->redis->rpush("$this->channel.reserved", $payload);
                }
            }
        }

        // Find a new reserved message
        if (!$wait) {
            return $this->redis->rpop("$this->channel.reserved");
        } elseif ($result = $this->redis->brpop("$this->channel.reserved", $wait)) {
            return $result[1];
        } else {
            return null;
        }
    }

    private $now = 0;

    /**
     * @inheritdoc
     */
    protected function sendMessage($message, $timeout)
    {
        $id = $this->redis->incr("$this->channel.message_id");
        $payload = "$id:$message";
        if (!$timeout) {
            $this->redis->lpush("$this->channel.reserved", $payload);
        } else {
            $this->redis->zadd("$this->channel.delayed", time() + $timeout, $payload);
        }
    }

    protected function openWorker()
    {
        $id = $this->redis->incr("$this->channel.worker_id");
        $this->redis->executeCommand('CLIENT', ['SETNAME', "$this->channel.worker.$id"]);
    }

    protected function closeWorker()
    {
        $this->redis->executeCommand('CLIENT', ['SETNAME', '']);
    }
}