<?php

/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

declare(strict_types=1);

namespace yii\queue\db;

use yii\base\Exception;
use yii\base\InvalidArgumentException;
use yii\db\Connection;
use yii\db\Query;
use yii\di\Instance;
use yii\mutex\Mutex;
use yii\queue\cli\Queue as CliQueue;
use yii\queue\interfaces\StatisticsInterface;
use yii\queue\interfaces\StatisticsProviderInterface;

/**
 * Db Queue.
 *
 * @property-read StatisticsProvider $statisticsProvider
 *
 * @author Roman Zhuravlev <zhuravljov@gmail.com>
 */
class Queue extends CliQueue implements StatisticsProviderInterface
{
    /**
     * @var Connection|array|string
     */
    public Connection|string|array $db = 'db';
    /**
     * @var Mutex|array|string
     */
    public Mutex|string|array $mutex = 'mutex';
    /**
     * @var int timeout
     */
    public int $mutexTimeout = 3;
    /**
     * @var string table name
     */
    public string $tableName = '{{%queue}}';
    /**
     * @var string
     */
    public string $channel = 'queue';
    /**
     * @var bool ability to delete released messages from table
     */
    public bool $deleteReleased = true;
    /**
     * @var string command class name
     */
    public string $commandClass = Command::class;

    protected int $reserveTime = 0;

    /**
     * @inheritdoc
     */
    public function init(): void
    {
        parent::init();
        $this->db = Instance::ensure($this->db, Connection::class);
        $this->mutex = Instance::ensure($this->mutex, Mutex::class);
    }

    /**
     * Listens queue and runs each job.
     *
     * @param bool $repeat whether to continue listening when queue is empty.
     * @param int<0, max> $timeout number of seconds to sleep before next iteration.
     * @return null|int exit code.
     * @internal for worker command only
     * @since 2.0.2
     */
    public function run(bool $repeat, int $timeout = 0)
    {
        return $this->runWorker(function (callable $canContinue) use ($repeat, $timeout) {
            while ($canContinue()) {
                if ($payload = $this->reserve()) {
                    /** @var array{id: int|string, job:string, ttr:int|string, attempt:int|string} $payload */
                    if (
                        $this->handleMessage(
                            $payload['id'],
                            $payload['job'],
                            (int) $payload['ttr'],
                            (int) $payload['attempt']
                        )
                    ) {
                        $this->release($payload);
                    }
                } elseif (!$repeat) {
                    break;
                } elseif ($timeout) {
                    sleep($timeout);
                }
            }
        });
    }

    /**
     * @inheritdoc
     */
    public function status(int|string $id): int
    {
        $payload = (new Query())
            ->from($this->tableName)
            ->where(['id' => $id])
            ->one($this->getDb());

        if (!$payload) {
            if ($this->deleteReleased) {
                return self::STATUS_DONE;
            }

            throw new InvalidArgumentException("Unknown message ID: $id.");
        }

        if (!isset($payload['reserved_at'])) {
            return self::STATUS_WAITING;
        }

        if (!isset($payload['done_at'])) {
            return self::STATUS_RESERVED;
        }

        return self::STATUS_DONE;
    }

    /**
     * Clears the queue.
     *
     * @since 2.0.1
     */
    public function clear(): void
    {
        $this->getDb()->createCommand()
            ->delete($this->tableName, ['channel' => $this->channel])
            ->execute();
    }

    /**
     * Removes a job by ID.
     *
     * @param int $id of a job
     * @return bool
     * @since 2.0.1
     */
    public function remove(int $id): bool
    {
        return (bool) $this->getDb()->createCommand()
            ->delete($this->tableName, ['channel' => $this->channel, 'id' => $id])
            ->execute();
    }

    /**
     * @inheritdoc
     */
    protected function pushMessage(string $payload, int $ttr, int $delay, mixed $priority): int|string|null
    {
        $this->getDb()->createCommand()->insert($this->tableName, [
            'channel' => $this->channel,
            'job' => $payload,
            'pushed_at' => time(),
            'ttr' => $ttr,
            'delay' => $delay,
            'priority' => $priority ?: 1024,
        ])->execute();
        $tableSchema = $this->getDb()->getTableSchema($this->tableName);
        if (null === $tableSchema) {
            return null;
        }
        return $this->getDb()->getLastInsertID($tableSchema->sequenceName ?? '');
    }

    /**
     * Takes one message from waiting list and reserves it for handling.
     *
     * @return array|false
     * @throws Exception in case it hasn't waited the lock
     */
    protected function reserve(): bool|array
    {
        return $this->getDb()->useMaster(function () {
            if (!$this->getMutex()->acquire(__CLASS__ . $this->channel, $this->mutexTimeout)) {
                throw new Exception('Has not waited the lock.');
            }

            try {
                $this->moveExpired();

                // Reserve one message
                $payload = (new Query())
                    ->from($this->tableName)
                    ->andWhere(['channel' => $this->channel, 'reserved_at' => null])
                    ->andWhere('[[pushed_at]] <= :time - [[delay]]', [':time' => time()])
                    ->orderBy(['priority' => SORT_ASC, 'id' => SORT_ASC])
                    ->limit(1)
                    ->one($this->getDb());
                if (is_array($payload)) {
                    $payload['reserved_at'] = time();
                    $payload['attempt'] = (int) $payload['attempt'] + 1;
                    $this->getDb()->createCommand()->update($this->tableName, [
                        'reserved_at' => $payload['reserved_at'],
                        'attempt' => $payload['attempt'],
                    ], [
                        'id' => $payload['id'],
                    ])->execute();

                    // pgsql
                    if (is_resource($payload['job'])) {
                        $payload['job'] = stream_get_contents($payload['job']);
                    }
                }
            } finally {
                $this->getMutex()->release(__CLASS__ . $this->channel);
            }

            return $payload;
        });
    }

    /**
     * @param array $payload
     */
    protected function release(array $payload): void
    {
        if ($this->deleteReleased) {
            $this->getDb()->createCommand()->delete(
                $this->tableName,
                ['id' => $payload['id']]
            )->execute();
        } else {
            $this->getDb()->createCommand()->update(
                $this->tableName,
                ['done_at' => time()],
                ['id' => $payload['id']]
            )->execute();
        }
    }

    /**
     * Moves expired messages into waiting list.
     */
    protected function moveExpired(): void
    {
        if ($this->reserveTime !== time()) {
            $this->reserveTime = time();
            $this->getDb()->createCommand()->update(
                $this->tableName,
                ['reserved_at' => null],
                // `reserved_at IS NOT NULL` forces db to use index on column,
                // otherwise a full scan of the table will be performed
                '[[reserved_at]] is not null and [[reserved_at]] < :time - [[ttr]] and [[done_at]] is null',
                [':time' => $this->reserveTime]
            )->execute();
        }
    }

    private function getDb(): Connection
    {
        /** @var Connection $dbConnection */
        $dbConnection = $this->db;
        if (is_string($this->db) || is_array($this->db)) {
            $this->db = Instance::ensure($this->db, Connection::class);
        }
        return $dbConnection;
    }

    private function getMutex(): Mutex
    {
        /** @var Mutex $mutex */
        $mutex = $this->mutex;
        if (is_string($this->mutex) || is_array($this->mutex)) {
            $this->mutex = Instance::ensure($this->mutex, Mutex::class);
        }
        return $mutex;
    }

    private StatisticsInterface $statisticsProvider;

    /**
     * @return StatisticsInterface
     */
    public function getStatisticsProvider(): StatisticsInterface
    {
        if (!isset($this->statisticsProvider)) {
            $this->statisticsProvider = new StatisticsProvider($this);
        }
        return $this->statisticsProvider;
    }
}
