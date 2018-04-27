<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\queue\gearman;

use yii\base\NotSupportedException;
use yii\queue\cli\Queue as CliQueue;

/**
 * Gearman Queue.
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
     * Listens queue and runs each job.
     *
     * @param bool $repeat whether to continue listening when queue is empty.
     * @return null|int exit code.
     * @internal for worker command only.
     * @since 2.0.2
     */
    public function run($repeat)
    {
        return $this->runWorker(function (callable $canContinue) use ($repeat) {
            $worker = new \GearmanWorker();
            $worker->addServer($this->host, $this->port);
            $worker->addFunction($this->channel, function (\GearmanJob $payload) {
                list($ttr, $message) = explode(';', $payload->workload(), 2);
                $this->handleMessage($payload->handle(), $message, $ttr, 1);
            });
            $worker->setTimeout($repeat ? 1000 : 1);
            while ($canContinue()) {
                $result = $worker->work();
                if (!$result && !$repeat) {
                    break;
                }
            }
        });
    }

    /**
     * @inheritdoc
     */
    protected function pushMessage($message, $ttr, $delay, $priority)
    {
        if ($delay) {
            throw new NotSupportedException('Delayed work is not supported in the driver.');
        }

        switch ($priority) {
            case 'high':
                return $this->getClient()->doHighBackground($this->channel, "$ttr;$message");
            case 'low':
                return $this->getClient()->doLowBackground($this->channel, "$ttr;$message");
            default:
                return $this->getClient()->doBackground($this->channel, "$ttr;$message");
        }
    }

    /**
     * @inheritdoc
     */
    public function status($id)
    {
        $status = $this->getClient()->jobStatus($id);
        if ($status[0] && !$status[1]) {
            return self::STATUS_WAITING;
        }

        if ($status[0] && $status[1]) {
            return self::STATUS_RESERVED;
        }

        return self::STATUS_DONE;
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
