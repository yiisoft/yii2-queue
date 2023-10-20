<?php

declare(strict_types=1);

/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

namespace yii\queue\beanstalk;

use Exception;
use Pheanstalk\Contract\PheanstalkPublisherInterface;
use Pheanstalk\Contract\SocketFactoryInterface;
use Pheanstalk\Pheanstalk;
use Pheanstalk\Values\JobId;
use Pheanstalk\Values\Timeout;
use Pheanstalk\Values\TubeName;
use Pheanstalk\Values\TubeStats;
use yii\base\InvalidArgumentException;
use yii\queue\cli\Queue as CliQueue;

/**
 * Beanstalk Queue.
 *
 * @property-read TubeName $tubeName
 * @property-read object $statsTube Tube statistics.
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
    public int $port = SocketFactoryInterface::DEFAULT_PORT;
    /**
     * @var int|null connection timeout in seconds
     */
    public ?int $connectTimeout = null;
    /**
     * @var int|null receive timeout in seconds
     */
    public ?int $receiveTimeout = null;
    /**
     * @var string beanstalk tube
     */
    public string $tube = 'queue';
    /**
     * @var string command class name
     */
    public string $commandClass = Command::class;

    private ?Pheanstalk $pheanstalk = null;

    /**
     * Listens queue and runs each job.
     *
     * @param bool $repeat whether to continue listening when queue is empty.
     * @param int<0, max> $timeout number of seconds to wait for next message.
     * @return null|int exit code.
     * @internal for worker command only.
     * @since 2.0.2
     */
    public function run(bool $repeat, int $timeout = 0): ?int
    {
        return $this->runWorker(function (callable $canContinue) use ($repeat, $timeout) {
            while ($canContinue()) {
                $pheanstalk = $this->getPheanstalk();
                $pheanstalk->watch($this->getTubeName());

                $job = $pheanstalk->reserveWithTimeout($timeout);
                if (null !== $job) {
                    try {
                        $info = $pheanstalk->statsJob($job);

                        if ($this->handleMessage(
                            $job->getId(),
                            $job->getData(),
                            $info->timeToRelease,
                            $info->reserves
                        )) {
                            $pheanstalk->delete($job);
                        }
                    } catch (Exception) {
                        $pheanstalk->release($job);
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
            $stats = $this->getPheanstalk()->statsJob(new JobId($id));

            if ($stats->state->value === 'reserved') {
                return self::STATUS_RESERVED;
            }

            return self::STATUS_WAITING;
        } catch (\Throwable) {
            return self::STATUS_DONE;
        }
    }

    /**
     * Removes a job by ID.
     *
     * @param int|string $id of a job
     * @return bool
     * @since 2.0.1
     */
    public function remove(int|string $id): bool
    {
        try {
            $this->getPheanstalk()->delete(new JobId($id));
            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * @inheritdoc
     */
    protected function pushMessage(string $payload, int $ttr, int $delay, mixed $priority): int|string|null
    {
        $pheanstalk = $this->getPheanstalk();
        $pheanstalk->useTube($this->getTubeName());

        $result = $pheanstalk
            ->put(
                $payload,
                $priority ?: PheanstalkPublisherInterface::DEFAULT_PRIORITY,
                $delay, // Seconds to wait before job becomes ready
                $ttr // Time To Run: seconds a job can be reserved for
            );
        return $result->getId();
    }

    /**
     * @return TubeStats tube statistics
     */
    public function getStatsTube(): TubeStats
    {
        return $this->getPheanstalk()->statsTube($this->getTubeName());
    }

    protected function getPheanstalk(): Pheanstalk
    {
        if (null === $this->pheanstalk) {
            $this->pheanstalk = Pheanstalk::create(
                $this->host,
                $this->port,
                $this->getConnectTimeout(),
                $this->getReceiveTimeout()
            );
        }
        return $this->pheanstalk;
    }

    protected function getTubeName(): TubeName
    {
        return new TubeName($this->tube);
    }

    private function getConnectTimeout(): ?Timeout
    {
        if (null === $this->connectTimeout) {
            return null;
        }
        return new Timeout($this->connectTimeout);
    }

    private function getReceiveTimeout(): ?Timeout
    {
        if (null === $this->receiveTimeout) {
            return null;
        }
        return new Timeout($this->receiveTimeout);
    }
}
