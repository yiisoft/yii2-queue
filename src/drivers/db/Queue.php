<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\queue\db;

use yii\base\Exception;
use yii\base\InvalidParamException;
use yii\db\Connection;
use yii\db\Query;
use yii\di\Instance;
use yii\mutex\Mutex;
use yii\queue\cli\Queue as CliQueue;
use yii\queue\cli\Signal;

/**
 * Db Queue
 *
 * @author Roman Zhuravlev <zhuravljov@gmail.com>
 */
class Queue extends CliQueue
{
    /**
     * @var Connection|array|string
     */
    public $db = 'db';
    /**
     * @var Mutex|array|string
     */
    public $mutex = 'mutex';
    /**
     * @var int timeout
     */
    public $mutexTimeout = 3;
    /**
     * @var string table name
     */
    public $tableName = '{{%queue}}';
    /**
     * @var string
     */
    public $channel = 'queue';
    /**
     * @var boolean ability to delete released messages from table
     */
    public $deleteReleased = true;

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
        $this->db = Instance::ensure($this->db, Connection::class);
        $this->mutex = Instance::ensure($this->mutex, Mutex::class);
    }

    /**
     * Runs all jobs from db-queue.
     */
    public function run()
    {
        while (!Signal::isExit() && ($payload = $this->reserve())) {
            if ($this->handleMessage(
                $payload['id'],
                $payload['job'],
                $payload['ttr'],
                $payload['attempt']
            )) {
                $this->release($payload);
            }
        }
    }

    /**
     * Listens db-queue and runs new jobs.
     *
     * @param integer $delay number of seconds for waiting new job.
     */
    public function listen($delay)
    {
        do {
            $this->run();
        } while (!$delay || sleep($delay) === 0);
    }

    /**
     * @inheritdoc
     */
    protected function pushMessage($message, $ttr, $delay, $priority)
    {
        $this->db->createCommand()->insert($this->tableName, [
            'channel' => $this->channel,
            'job' => $message,
            'pushed_at' => time(),
            'ttr' => $ttr,
            'delay' => $delay,
            'priority' => $priority ?: 1024,
        ])->execute();
        $tableSchema = $this->db->getTableSchema($this->tableName);
        $id = $this->db->getLastInsertID($tableSchema->sequenceName);

        return $id;
    }

    /**
     * @inheritdoc
     */
    protected function status($id)
    {
        $payload = (new Query())
            ->from($this->tableName)
            ->where(['id' => $id])
            ->one($this->db);

        if (!$payload) {
            if ($this->deleteReleased) {
                return self::STATUS_DONE;
            } else {
                throw new InvalidParamException("Unknown messages ID: $id.");
            }
        }

        if (!$payload['reserved_at']) {
            return self::STATUS_WAITING;
        } elseif (!$payload['done_at']) {
            return self::STATUS_RESERVED;
        } else {
            return self::STATUS_DONE;
        }
    }

    /**
     * @return array|false payload
     * @throws Exception in case it hasn't waited the lock
     */
    protected function reserve()
    {
        if (!$this->mutex->acquire(__CLASS__ . $this->channel, $this->mutexTimeout)) {
            throw new Exception("Has not waited the lock.");
        }

        // Move reserved and not done messages into waiting list

        if ($this->reserveTime !== time()) {
            $this->reserveTime = time();
            $this->db->createCommand()->update(
                $this->tableName,
                ['reserved_at' => null],
                '[[reserved_at]] < :time - [[ttr]] and [[done_at]] is null',
                [':time' => $this->reserveTime]
            )->execute();
        }

        // Reserve one message

        $payload = (new Query())
            ->from($this->tableName)
            ->andWhere(['channel' => $this->channel, 'reserved_at' => null])
            ->andWhere('[[pushed_at]] <= :time - delay', [':time' => time()])
            ->orderBy(['priority' => SORT_ASC, 'id' => SORT_ASC])
            ->limit(1)
            ->one($this->db);

        if (is_array($payload)) {
            $payload['reserved_at'] = time();
            $payload['attempt'] = (int)$payload['attempt'] + 1;
            $this->db->createCommand()->update($this->tableName, [
                'reserved_at' => $payload['reserved_at'], 'attempt' => $payload['attempt']],
                ['id' => $payload['id']]
            )->execute();
        }

        $this->mutex->release(__CLASS__ . $this->channel);

        // pgsql
        if (is_resource($payload['job'])) {
            $payload['job'] = stream_get_contents($payload['job']);
        }

        return $payload;
    }

    private $reserveTime;

    /**
     * @param array $payload
     */
    protected function release($payload)
    {
        if ($this->deleteReleased) {
            $this->db->createCommand()->delete(
                $this->tableName,
                ['id' => $payload['id']]
            )->execute();
        } else {
            $this->db->createCommand()->update(
                $this->tableName,
                ['done_at' => time()],
                ['id' => $payload['id']]
            )->execute();
        }
    }
}