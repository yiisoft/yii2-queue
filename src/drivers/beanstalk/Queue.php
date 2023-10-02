<?php

declare(strict_types=1);

/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

namespace yii\queue\beanstalk;

use Pheanstalk\Exception\ServerException;
use Pheanstalk\Job;
use Pheanstalk\Pheanstalk;
use Pheanstalk\PheanstalkInterface;
use yii\base\InvalidArgumentException;
use yii\queue\cli\Queue as CliQueue;

/**
 * Beanstalk Queue.
 *
 * @author Roman Zhuravlev <zhuravljov@gmail.com>
 */
class Queue extends CliQueue
{
    /**
     * @var string connection host
     */
    public string $host = 'localhost';
    /**
     * @var int connection port
     */
    public int $port = PheanstalkInterface::DEFAULT_PORT;
    /**
     * @var string beanstalk tube
     */
    public string $tube = 'queue';
    /**
     * @var string command class name
     */
    public string $commandClass = Command::class;

    /**
     * Listens queue and runs each job.
     *
     * @param bool $repeat whether to continue listening when queue is empty.
     * @param int $timeout number of seconds to wait for next message.
     * @return null|int exit code.
     * @internal for worker command only.
     * @since 2.0.2
     */
    public function run(bool $repeat, int $timeout = 0): ?int
    {
        return $this->runWorker(function (callable $canContinue) use ($repeat, $timeout) {
            while ($canContinue()) {
                if ($payload = $this->getPheanstalk()->reserveFromTube($this->tube, $timeout)) {
                    $info = $this->getPheanstalk()->statsJob($payload);
                    if ($this->handleMessage(
                        $payload->getId(),
                        $payload->getData(),
                        $info->ttr,
                        $info->reserves
                    )) {
                        $this->getPheanstalk()->delete($payload);
                    }
                } elseif (!$repeat) {
                    break;
                }
            }
        });
    }

    /**
     * @inheritdoc
     */
    public function status($id): int
    {
        if (!is_numeric($id) || $id <= 0) {
            throw new InvalidArgumentException("Unknown message ID: $id.");
        }

        try {
            $stats = $this->getPheanstalk()->statsJob($id);
            if ($stats['state'] === 'reserved') {
                return self::STATUS_RESERVED;
            }

            return self::STATUS_WAITING;
        } catch (ServerException $e) {
            if ($e->getMessage() === 'Server reported NOT_FOUND') {
                return self::STATUS_DONE;
            }

            throw $e;
        }
    }

    /**
     * Removes a job by ID.
     *
     * @param int $id of a job
     * @return bool
     * @since 2.0.1
     */
    public function remove($id): bool
    {
        try {
            $this->getPheanstalk()->delete(new Job($id, null));
            return true;
        } catch (ServerException $e) {
            if (str_starts_with($e->getMessage(), 'NOT_FOUND')) {
                return false;
            }

            throw $e;
        }
    }

    /**
     * @inheritdoc
     */
    protected function pushMessage(string $payload, int $ttr, int $delay, mixed $priority): int|string|null
    {
        return $this->getPheanstalk()->putInTube(
            $this->tube,
            $payload,
            $priority ?: PheanstalkInterface::DEFAULT_PRIORITY,
            $delay,
            $ttr
        );
    }

    /**
     * @return object tube statistics
     */
    public function getStatsTube()
    {
        return $this->getPheanstalk()->statsTube($this->tube);
    }

    /**
     * @return Pheanstalk
     */
    protected function getPheanstalk()
    {
        if (!$this->_pheanstalk) {
            $this->_pheanstalk = new Pheanstalk($this->host, $this->port);
        }
        return $this->_pheanstalk;
    }

    private $_pheanstalk;
}
