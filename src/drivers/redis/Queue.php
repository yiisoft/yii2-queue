<?php
/**
 * @link https://github.com/zhuravljov/yii2-queue
 * @copyright Copyright (c) 2017 Roman Zhuravlev
 * @license http://opensource.org/licenses/BSD-3-Clause
 */

namespace zhuravljov\yii\queue\redis;

use yii\base\InvalidParamException;
use yii\base\NotSupportedException;
use yii\di\Instance;
use yii\redis\Connection;
use zhuravljov\yii\queue\cli\Queue as CliQueue;
use zhuravljov\yii\queue\cli\Signal;

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
            list($id, $message) = $payload;
            $this->handleMessage($id, $message);
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
                list($id, $message) = $payload;
                $this->handleMessage($id, $message);
            }
        }
        $this->closeWorker();
    }

    /**
     * @param int $wait timeout
     * @return array|null payload
     */
    protected function pop($wait)
    {
        // Move delayed messages into waiting
        if ($this->now < time()) {
            $this->now = time();
            if ($delayed = $this->redis->zrevrangebyscore("$this->channel.delayed", $this->now, '-inf')) {
                $this->redis->zremrangebyscore("$this->channel.delayed", '-inf', $this->now);
                foreach ($delayed as $id) {
                    $this->redis->rpush("$this->channel.waiting", $id);
                }
            }
        }

        // Find a new waiting message
        if (!$wait) {
            if ($id = $this->redis->rpop("$this->channel.waiting")) {
                $message = $this->redis->hget("$this->channel.messages", $id);
                $this->redis->hdel("$this->channel.messages", $id);

                return [$id, $message];
            }
        } else {
            if ($result = $this->redis->brpop("$this->channel.waiting", $wait)) {
                $id = $result[1];
                $message = $this->redis->hget("$this->channel.messages", $id);
                $this->redis->hdel("$this->channel.messages", $id);

                return [$id, $message];
            }
        }

        return null;
    }

    private $now = 0;

    /**
     * @inheritdoc
     */
    protected function pushMessage($message, $delay, $priority)
    {
        if ($priority !== null) {
            throw new NotSupportedException('Job priority is not supported in the driver.');
        }

        $id = $this->redis->incr("$this->channel.message_id");
        if (!$delay) {
            $this->redis->hset("$this->channel.messages", $id, $message);
            $this->redis->lpush("$this->channel.waiting", $id);
        } else {
            $this->redis->hset("$this->channel.messages", $id, $message);
            $this->redis->zadd("$this->channel.delayed", time() + $delay, $id);
        }

        return $id;
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

    /**
     * @inheritdoc
     */
    protected function status($id)
    {
        if (!is_numeric($id) || $id <= 0) {
            throw new InvalidParamException("Unknown messages ID: $id.");
        }

        if ($this->redis->hexists("$this->channel.messages", $id)) {
            return self::STATUS_WAITING;
        } else {
            return self::STATUS_DONE;
        }
    }
}