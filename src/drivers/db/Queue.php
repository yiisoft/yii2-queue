<?php
/**
 * @link https://github.com/zhuravljov/yii2-queue
 * @copyright Copyright (c) 2017 Roman Zhuravlev
 * @license http://opensource.org/licenses/BSD-3-Clause
 */

namespace zhuravljov\yii\queue\db;

use yii\base\Exception;
use yii\base\InvalidParamException;
use yii\base\NotSupportedException;
use yii\db\Connection;
use yii\db\Query;
use yii\di\Instance;
use yii\mutex\Mutex;
use zhuravljov\yii\queue\cli\Queue as CliQueue;
use zhuravljov\yii\queue\cli\Signal;

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
    public $deleteReleased = false;

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
        while (!Signal::isExit() && ($payload = $this->pop())) {
            if ($this->handleMessage($payload['id'], $payload['job'])) {
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
    protected function pushMessage($message, $delay, $priority)
    {
        if ($priority !== null) {
            throw new NotSupportedException('Job priority is not supported in the driver.');
        }

        $this->db->createCommand()->insert($this->tableName, [
            'channel' => $this->channel,
            'job' => $message,
            'created_at' => time(),
            'timeout' => $delay,
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

        if (!$payload['started_at']) {
            return self::STATUS_WAITING;
        } elseif (!$payload['finished_at']) {
            return self::STATUS_RESERVED;
        } else {
            return self::STATUS_DONE;
        }
    }

    /**
     * @return array|false payload
     * @throws Exception in case it hasn't waited the lock
     */
    protected function pop()
    {
        if (!$this->mutex->acquire(__CLASS__ . $this->channel, $this->mutexTimeout)) {
            throw new Exception("Has not waited the lock.");
        }

        $payload = (new Query())
            ->from($this->tableName)
            ->andWhere(['channel' => $this->channel, 'started_at' => null])
            ->andWhere('created_at <= :time - timeout', [':time' => time()])
            ->orderBy(['id' => SORT_ASC])
            ->limit(1)
            ->one($this->db);

        if (is_array($payload)) {
            $payload['started_at'] = time();
            $this->db->createCommand()->update(
                $this->tableName,
                ['started_at' => $payload['started_at']],
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
                ['finished_at' => time()],
                ['id' => $payload['id']]
            )->execute();
        }
    }
}