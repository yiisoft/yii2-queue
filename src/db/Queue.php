<?php
/**
 * @link https://github.com/zhuravljov/yii2-queue
 * @copyright Copyright (c) 2017 Roman Zhuravlev
 * @license http://opensource.org/licenses/BSD-3-Clause
 */

namespace zhuravljov\yii\queue\db;

use yii\base\BootstrapInterface;
use yii\base\Exception;
use yii\console\Application as ConsoleApp;
use yii\db\Connection;
use yii\db\Query;
use yii\di\Instance;
use yii\mutex\Mutex;
use zhuravljov\yii\queue\Queue as BaseQueue;
use zhuravljov\yii\queue\Signal;

/**
 * Db Queue
 *
 * @author Roman Zhuravlev <zhuravljov@gmail.com>
 */
class Queue extends BaseQueue implements BootstrapInterface
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
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
        $this->db = Instance::ensure($this->db, Connection::class);
        $this->mutex = Instance::ensure($this->mutex, Mutex::class);
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
     * Runs all jobs from db-queue.
     */
    public function run()
    {
        while (!Signal::isExit() && ($payload = $this->pop())) {
            if ($this->handleMessage($payload['job'])) {
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
    protected function sendMessage($message, $timeout)
    {
        $this->db->createCommand()->insert($this->tableName, [
            'channel' => $this->channel,
            'job' => $message,
            'created_at' => time(),
            'timeout' => $timeout,
        ])->execute();
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