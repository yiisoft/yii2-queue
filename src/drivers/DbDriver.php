<?php

namespace zhuravljov\yii\queue\drivers;

use yii\db\Connection;
use yii\db\Query;
use yii\di\Instance;
use yii\mutex\Mutex;
use zhuravljov\yii\queue\Driver;

/**
 * Class DbDriver
 *
 * @author Roman Zhuravlev <zhuravljov@gmail.com>
 */
class DbDriver extends Driver
{
    /**
     * @var Connection|array|string
     */
    public $db = 'db';
    /**
     * @var Mutex
     */
    public $mutex = 'mutex';
    /**
     * @var string table name
     */
    public $tableName = '{{%queue}}';

    public function init()
    {
        parent::init();
        $this->db = Instance::ensure($this->db, Connection::class);
        $this->mutex = Instance::ensure($this->mutex, Mutex::class);
    }

    /**
     * @inheritdoc
     */
    public function push($job)
    {
        $this->db->createCommand()->insert(
            $this->tableName,
            ['job' => serialize($job), 'created_at' => time()]
        )->execute();

        return (new Query())
            ->from($this->tableName)
            ->where(['id' => $this->db->lastInsertID])
            ->one($this->db);
    }

    /**
     * @inheritdoc
     */
    public function pop(&$message, &$job)
    {
        $message = (new Query())
            ->from($this->tableName)
            ->where(['started_at' => null])
            ->orderBy(['id' => SORT_ASC])
            ->limit(1)
            ->one($this->db);

        if ($message) {
            $message['started_at'] = time();
            $this->db->createCommand()->update(
                $this->tableName,
                ['started_at' => $message['started_at']],
                ['id' => $message['id']]
            )->execute();

            $job = unserialize($message['job']);
        }

        return !!$message;
    }

    /**
     * @inheritdoc
     */
    public function release($message)
    {
        $this->db->createCommand()->update(
            $this->tableName,
            ['finished_at' => time()],
            ['id' => $message['id']]
        )->execute();
    }
}