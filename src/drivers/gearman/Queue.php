<?php

declare(strict_types=1);

/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

namespace yii\queue\gearman;

use GearmanClient;
use GearmanJob;
use GearmanWorker;
use yii\base\NotSupportedException;
use yii\queue\cli\Queue as CliQueue;

/**
 * Gearman Queue.
 *
 * @author Roman Zhuravlev <zhuravljov@gmail.com>
 */
class Queue extends CliQueue
{
    public string $host = 'localhost';
    public int $port = 4730;
    public string $channel = 'queue';
    /**
     * @var string command class name
     */
    public string $commandClass = Command::class;

    /**
     * Listens queue and runs each job.
     *
     * @param bool $repeat whether to continue listening when queue is empty.
     * @return null|int exit code.
     * @internal for worker command only.
     * @since 2.0.2
     */
    public function run(bool $repeat): ?int
    {
        return $this->runWorker(function (callable $canContinue) use ($repeat) {
            $worker = new GearmanWorker();
            $worker->addServer($this->host, $this->port);
            $worker->addFunction($this->channel, function (GearmanJob $payload) {
                [$ttr, $message] = explode(';', $payload->workload(), 2);
                $this->handleMessage($payload->handle(), $message, (int)$ttr, 1);
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
    protected function pushMessage(string $payload, int $ttr, int $delay, mixed $priority): int|string|null
    {
        if ($delay) {
            throw new NotSupportedException('Delayed work is not supported in the driver.');
        }

        return match ($priority) {
            'high' => $this->getClient()->doHighBackground($this->channel, "$ttr;$payload"),
            'low' => $this->getClient()->doLowBackground($this->channel, "$ttr;$payload"),
            default => $this->getClient()->doBackground($this->channel, "$ttr;$payload"),
        };
    }

    /**
     * @inheritdoc
     */
    public function status($id): int
    {
        $status = $this->getClient()->jobStatus($id);
        if ($status[0] && !$status[1]) {
            return self::STATUS_WAITING;
        }

        /** @psalm-suppress RedundantCondition */
        if ($status[0] && $status[1]) {
            return self::STATUS_RESERVED;
        }

        return self::STATUS_DONE;
    }

    /**
     * @return GearmanClient
     */
    protected function getClient(): GearmanClient
    {
        if (!$this->_client) {
            $this->_client = new GearmanClient();
            $this->_client->addServer($this->host, $this->port);
        }
        return $this->_client;
    }

    private $_client;
}
