<?php
/**
 * @link https://github.com/zhuravljov/yii2-queue
 * @copyright Copyright (c) 2017 Roman Zhuravlev
 * @license http://opensource.org/licenses/BSD-3-Clause
 */

namespace zhuravljov\yii\queue\drivers\redis;

use yii\di\Instance;
use yii\redis\Connection;
use zhuravljov\yii\queue\CliQueue;
use zhuravljov\yii\queue\Signal;

/**
 * Redis Queue
 *
 * @author Roman Zhuravlev <zhuravljov@gmail.com>
 */
class Queue extends CliQueue
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
     * @var string command class name
     */
    public $commandClass = Command::class;

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
        $this->redis = Instance::ensure($this->redis, Connection::class);
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
    protected function pushMessage($message, $timeout)
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
        $this->redis->clientSetname("$this->channel.worker.$id");
    }

    protected function closeWorker()
    {
        $this->redis->clientSetname('');
    }
}