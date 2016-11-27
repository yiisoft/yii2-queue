<?php

namespace zhuravljov\yii\queue\db;

use yii\base\BootstrapInterface;
use yii\db\Connection;
use yii\db\Query;
use yii\di\Instance;
use yii\mutex\Mutex;
use zhuravljov\yii\queue\Driver as BaseDriver;

/**
 * Class DbDriver
 *
 * @author Roman Zhuravlev <zhuravljov@gmail.com>
 */
class Driver extends BaseDriver implements BootstrapInterface
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
     * @var string table name
     */
    public $tableName = '{{%queue}}';
    /**
     * @var boolean ability to delete released messages from table
     */
    public $deleteReleased = false;

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
        if ($app instanceof \yii\console\Application) {
            $app->controllerMap[$this->queue->id] = [
                'class' => Command::class,
                'queue' => $this->queue,
            ];
        }
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
    public function work($handler)
    {
        $count = 0;
        while ($message = $this->pop()) {
            $count++;
            $job = unserialize($message['job']);
            call_user_func($handler, $job);
            $this->release($message);
        }
        return $count;
    }

    protected function pop()
    {
        $this->mutex->acquire(__CLASS__);

        $message = (new Query())
            ->from($this->tableName)
            ->where(['started_at' => null])
            ->orderBy(['id' => SORT_ASC])
            ->limit(1)
            ->one($this->db);

        if (is_array($message)) {
            $message['started_at'] = time();
            $this->db->createCommand()->update(
                $this->tableName,
                ['started_at' => $message['started_at']],
                ['id' => $message['id']]
            )->execute();
        }

        $this->mutex->release(__CLASS__);

        return $message;
    }

    protected function release($message)
    {
        if ($this->deleteReleased) {
            $this->db->createCommand()->delete(
                $this->tableName,
                ['id' => $message['id']]
            )->execute();
        } else {
            $this->db->createCommand()->update(
                $this->tableName,
                ['finished_at' => time()],
                ['id' => $message['id']]
            )->execute();
        }
    }

    /**
     * @inheritdoc
     */
    public function purge()
    {
        $this->mutex->acquire(__CLASS__);

        $this->db->createCommand()->delete(
            $this->tableName,
            ['started_at' => null]
        )->execute();

        $this->mutex->release(__CLASS__);
    }
}