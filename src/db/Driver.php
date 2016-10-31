<?php

namespace zhuravljov\yii\queue\db;

use yii\base\BootstrapInterface;
use yii\db\Connection;
use yii\db\Query;
use yii\di\Instance;
use yii\mutex\Mutex;
use zhuravljov\yii\queue\BaseDriver;

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
                'queue' => $this,
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
    public function pop(&$message, &$job)
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

        if (is_array($message)) {
            $job = unserialize($message['job']);
            return true;
        } else {
            return false;
        }
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