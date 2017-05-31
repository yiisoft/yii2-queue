<?php
/**
 * @link https://github.com/zhuravljov/yii2-queue
 * @copyright Copyright (c) 2017 Roman Zhuravlev
 * @license http://opensource.org/licenses/BSD-3-Clause
 */

namespace zhuravljov\yii\queue\gearman;

use yii\base\NotSupportedException;
use zhuravljov\yii\queue\cli\Queue as CliQueue;
use zhuravljov\yii\queue\cli\Signal;

/**
 * Gearman Queue
 *
 * @author Roman Zhuravlev <zhuravljov@gmail.com>
 */
class Queue extends CliQueue
{
    public $host = 'localhost';
    public $port = 4730;
    public $channel = 'queue';

    /**
     * @var string command class name
     */
    public $commandClass = Command::class;

    /**
     * Runs all jobs from gearman-queue.
     */
    public function run()
    {
        $worker = new \GearmanWorker();
        $worker->addServer($this->host, $this->port);
        $worker->addFunction($this->channel, function (\GearmanJob $message) {
            $this->handleMessage($message->handle(), $message->workload());
        });
        $worker->setTimeout(1);
        do {
            $worker->work();
        } while (!Signal::isExit() && $worker->returnCode() === GEARMAN_SUCCESS);

    }

    /**
     * Listens gearman-queue and runs new jobs.
     */
    public function listen()
    {
        $worker = new \GearmanWorker();
        $worker->addServer($this->host, $this->port);
        $worker->addFunction($this->channel, function (\GearmanJob $message) {
            $this->handleMessage($message->handle(), $message->workload());
        });

        $worker->setTimeout(1000);
        do {
            $worker->work();
        } while (
            !Signal::isExit() &&
            in_array($worker->returnCode(), [GEARMAN_TIMEOUT, GEARMAN_SUCCESS])
        );
    }

    /**
     * @inheritdoc
     */
    protected function pushMessage($message, $delay, $priority)
    {
        if ($delay) {
            throw new NotSupportedException('Delayed work is not supported in the driver.');
        }

        switch ($priority) {
            case 'high':
                return $this->getClient()->doHighBackground($this->channel, $message);
            case 'low':
                return $this->getClient()->doLowBackground($this->channel, $message);
            default:
                return $this->getClient()->doBackground($this->channel, $message);
        }
    }

    /**
     * @inheritdoc
     */
    protected function status($id)
    {
        $status = $this->getClient()->jobStatus($id);
        if ($status[0] && !$status[1]) {
            return self::STATUS_WAITING;
        } elseif ($status[0] && $status[1]) {
            return self::STATUS_RESERVED;
        } else {
            return self::STATUS_DONE;
        }
    }

    /**
     * @return \GearmanClient
     */
    protected function getClient()
    {
        if (!$this->_client) {
            $this->_client = new \GearmanClient();
            $this->_client->addServer($this->host, $this->port);
        }
        return $this->_client;
    }

    private $_client;
}