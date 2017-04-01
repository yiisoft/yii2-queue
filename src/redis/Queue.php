<?php
/**
 * @link https://github.com/zhuravljov/yii2-queue
 * @copyright Copyright (c) 2017 Roman Zhuravlev
 * @license http://opensource.org/licenses/BSD-3-Clause
 */

namespace zhuravljov\yii\queue\redis;

use yii\base\BootstrapInterface;
use yii\base\NotSupportedException;
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
        while (($result = $this->redis->executeCommand('LPOP', ["$this->channel.reserved"])) !== null) {
            $this->handleMessage($result);
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
            if ($result = $this->redis->executeCommand('BLPOP', ["$this->channel.reserved", 3])) {
                $this->handleMessage($result[1]);
            }
        }
        $this->closeWorker();
    }

    /**
     * @inheritdoc
     */
    protected function sendMessage($message, $timeout)
    {
        if ($timeout) {
            throw new NotSupportedException('Delayed work is not supported in the driver.');
        }

        $this->redis->executeCommand('RPUSH', ["$this->channel.reserved", $message]);
    }

    protected function openWorker()
    {
        $id = $this->redis->executeCommand('INCR', ["$this->channel.last_worker_id"]);
        $this->redis->executeCommand('CLIENT', ['SETNAME', "$this->channel.worker.$id"]);
    }

    protected function closeWorker()
    {
        $this->redis->executeCommand('CLIENT', ['SETNAME', '']);
    }
}