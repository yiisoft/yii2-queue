<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\queue\redis;

use yii\base\InvalidArgumentException;
use yii\base\NotSupportedException;
use yii\di\Instance;
use yii\queue\cli\Queue as CliQueue;
use yii\redis\Connection;
use yii\redis\Mutex;

/**
 * Redis Queue.
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
     * @var Mutex|array|string
     */
    public $mutex = [
        'class' => Mutex::class,
        'redis' => 'redis',
    ];

    /**
     * @var integer
     */
    public $mutexTimeout = 3;

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
        $this->mutex = Instance::ensure($this->mutex, Mutex::class);
    }

    /**
     * Listens queue and runs each job.
     *
     * @param bool $repeat whether to continue listening when queue is empty.
     * @param int $timeout number of seconds to wait for next message.
     * @return null|int exit code.
     * @internal for worker command only.
     * @since 2.0.2
     */
    public function run($repeat, $timeout = 0)
    {
        return $this->runWorker(function (callable $canContinue) use ($repeat, $timeout) {
            while ($canContinue()) {
                if ($this->acquire()) {
                    try {
                        $payload = $this->reserve($timeout);
                    } finally {
                        $this->release();
                    }

                    if ($payload !== null) {
                        list($id, $message, $ttr, $attempt) = $payload;
                        if ($this->handleMessage($id, $message, $ttr, $attempt)) {
                            $this->delete($id);
                        }
                    } elseif (!$repeat) {
                        break;
                    }
                }
            }
        });
    }

    /**
     * @inheritdoc
     */
    public function status($id)
    {
        if (!is_numeric($id) || $id <= 0) {
            throw new InvalidArgumentException("Unknown message ID: $id.");
        }

        if ($this->redis->hexists("$this->channel.attempts", $id)) {
            return self::STATUS_RESERVED;
        }

        if ($this->redis->hexists("$this->channel.messages", $id)) {
            return self::STATUS_WAITING;
        }

        return self::STATUS_DONE;
    }

    /**
     * Clears the queue.
     *
     * @since 2.0.1
     */
    public function clear()
    {
        while (!$this->acquire(0)) {
            usleep(10000);
        }

        try {
            $this->redis->executeCommand('DEL', $this->redis->keys("$this->channel.*"));
        } finally {
            $this->release();
        }
    }

    /**
     * Removes a job by ID.
     *
     * @param int $id of a job
     * @return bool
     * @since 2.0.1
     */
    public function remove($id)
    {
        while (!$this->acquire(0)) {
            usleep(10000);
        }

        try {
            if ($this->redis->hdel("$this->channel.messages", $id)) {
                $this->redis->zrem("$this->channel.delayed", $id);
                $this->redis->zrem("$this->channel.reserved", $id);
                $this->redis->lrem("$this->channel.waiting", 0, $id);
                $this->redis->hdel("$this->channel.attempts", $id);

                return true;
            }

            return false;

        } finally {
            $this->release();
        }
    }

    /**
     * @param int $timeout timeout
     * @return array|null payload
     */
    protected function reserve($timeout)
    {
        // Moves delayed and reserved jobs into waiting list
        $this->moveExpired("$this->channel.delayed");
        $this->moveExpired("$this->channel.reserved");

        // Find a new waiting message
        $id = null;
        if (!$timeout) {
            $id = $this->redis->rpop("$this->channel.waiting");
        } elseif ($result = $this->redis->brpop("$this->channel.waiting", $timeout)) {
            $id = $result[1];
        }
        if (!$id) {
            return null;
        }

        $payload = $this->redis->hget("$this->channel.messages", $id);
        list($ttr, $message) = explode(';', $payload, 2);
        $this->redis->zadd("$this->channel.reserved", time() + $ttr, $id);
        $attempt = $this->redis->hincrby("$this->channel.attempts", $id, 1);

        return [$id, $message, $ttr, $attempt];
    }

    /**
     * @param string $from
     */
    protected function moveExpired($from)
    {
        $now = time();
        if ($expired = $this->redis->zrevrangebyscore($from, $now, '-inf')) {
            $this->redis->zremrangebyscore($from, '-inf', $now);
            foreach ($expired as $id) {
                $this->redis->rpush("$this->channel.waiting", $id);
            }
        }
    }

    /**
     * Deletes message by ID.
     *
     * @param int $id of a message
     */
    protected function delete($id)
    {
        $this->redis->zrem("$this->channel.reserved", $id);
        $this->redis->hdel("$this->channel.attempts", $id);
        $this->redis->hdel("$this->channel.messages", $id);
    }

    /**
     * @inheritdoc
     */
    protected function pushMessage($message, $ttr, $delay, $priority)
    {
        if ($priority !== null) {
            throw new NotSupportedException('Job priority is not supported in the driver.');
        }

        $id = $this->redis->incr("$this->channel.message_id");
        $this->redis->hset("$this->channel.messages", $id, "$ttr;$message");
        if (!$delay) {
            $this->redis->lpush("$this->channel.waiting", $id);
        } else {
            $this->redis->zadd("$this->channel.delayed", time() + $delay, $id);
        }

        return $id;
    }

    /**
     * Acquire the lock.
     *
     * @return boolean
     */
    protected function acquire($timeout = null)
    {
        $timeout = $timeout !== null ? $timeout : $this->mutexTimeout;

        return $this->mutex->acquire(__CLASS__ . $this->channel, $timeout);
    }

    /**
     * Release the lock.
     *
     * @return boolean
     */
    protected function release()
    {
        return $this->mutex->release(__CLASS__ . $this->channel);
    }

}
