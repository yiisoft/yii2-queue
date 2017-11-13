<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\queue\db\migrations;

use yii\db\Migration;

/**
 * Example of migration for queue message storage
 *
 * @author Roman Zhuravlev <zhuravljov@gmail.com>
 */
class M170509001400Retry extends Migration
{
    public $tableName = '{{%queue}}';


    public function up()
    {
        if ($this->db->driverName !== 'sqlite') {
            $this->renameColumn($this->tableName, 'created_at', 'pushed_at');
            $this->addColumn($this->tableName, 'ttr', $this->integer()->notNull()->after('pushed_at'));
            $this->renameColumn($this->tableName, 'timeout', 'delay');
            $this->dropIndex('started_at', $this->tableName);
            $this->renameColumn($this->tableName, 'started_at', 'reserved_at');
            $this->createIndex('reserved_at', $this->tableName, 'reserved_at');
            $this->addColumn($this->tableName, 'attempt', $this->integer()->after('reserved_at'));
            $this->renameColumn($this->tableName, 'finished_at', 'done_at');
        } else {
            $this->dropTable($this->tableName);
            $this->createTable($this->tableName, [
                'id' => $this->primaryKey(),
                'channel' => $this->string()->notNull(),
                'job' => $this->binary()->notNull(),
                'pushed_at' => $this->integer()->notNull(),
                'ttr' => $this->integer()->notNull(),
                'delay' => $this->integer()->notNull(),
                'reserved_at' => $this->integer(),
                'attempt' => $this->integer(),
                'done_at' => $this->integer(),
            ]);
            $this->createIndex('channel', $this->tableName, 'channel');
            $this->createIndex('reserved_at', $this->tableName, 'reserved_at');
        }
    }

    public function down()
    {
        if ($this->db->driverName !== 'sqlite') {
            $this->renameColumn($this->tableName, 'done_at', 'finished_at');
            $this->dropColumn($this->tableName, 'attempt');
            $this->dropIndex('reserved_at', $this->tableName);
            $this->renameColumn($this->tableName, 'reserved_at', 'started_at');
            $this->createIndex('started_at', $this->tableName, 'started_at');
            $this->renameColumn($this->tableName, 'delay', 'timeout');
            $this->dropColumn($this->tableName, 'ttr');
            $this->renameColumn($this->tableName, 'pushed_at', 'created_at');
        } else {
            $this->dropTable($this->tableName);
            $this->createTable($this->tableName, [
                'id' => $this->primaryKey(),
                'channel' => $this->string()->notNull(),
                'job' => $this->binary()->notNull(),
                'created_at' => $this->integer()->notNull(),
                'timeout' => $this->integer()->notNull(),
                'started_at' => $this->integer(),
                'finished_at' => $this->integer(),
            ]);
            $this->createIndex('channel', $this->tableName, 'channel');
            $this->createIndex('started_at', $this->tableName, 'started_at');
        }
    }
}
