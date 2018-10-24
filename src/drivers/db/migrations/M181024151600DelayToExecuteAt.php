<?php

/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\queue\db\migrations;

use yii\db\Expression;
use yii\db\Migration;

/**
 * Example of migration for queue message storage.
 *
 * @author Roman Zhuravlev <zhuravljov@gmail.com>
 */
class M181024151600DelayToExecuteAt extends Migration
{
    public $tableName = '{{%queue}}';

    public function up()
    {
        $this->dropIndex('channel', $this->tableName, 'channel');
        if ($this->db->driverName !== 'sqlite') {
            $this->addColumn($this->tableName, 'execute_at', $this->integer()->notNull()->after('ttr'));
            $this->update($this->tableName, [
                'execute_at' => new Expression('[[pushed_at]] + [[delay]]')
            ]);
            $this->dropColumn($this->tableName, 'delay');
        } else {
            $this->dropTable($this->tableName);
            $this->createTable($this->tableName, [
                'id' => $this->primaryKey(),
                'channel' => $this->string()->notNull(),
                'job' => $this->binary()->notNull(),
                'pushed_at' => $this->integer()->notNull(),
                'ttr' => $this->integer()->notNull(),
                'execute_at' => $this->integer()->notNull(),
                'priority' => $this->integer()->unsigned()->notNull()->defaultValue(1024),
                'reserved_at' => $this->integer(),
                'attempt' => $this->integer(),
                'done_at' => $this->integer(),
            ]);
            $this->createIndex('reserved_at', $this->tableName, 'reserved_at');
            $this->createIndex('priority', $this->tableName, 'priority');
        }
        $this->createIndex('idx_queue__channel__execute_at', $this->tableName, ['channel', 'execute_at']);
    }

    public function down()
    {
        if ($this->db->driverName !== 'sqlite') {
            $this->dropIndex('idx_queue__channel__execute_at', $this->tableName);
            $this->addColumn($this->tableName, 'delay', $this->integer()->notNull()->after('ttr'));
            $this->update($this->tableName, [
                'delay' => new Expression('[[execute_at]] - [[pushed_at]]')
            ]);
            $this->dropColumn($this->tableName, 'execute_at');
        } else {
            $this->dropTable($this->tableName);
            $this->createTable($this->tableName, [
                'id' => $this->primaryKey(),
                'channel' => $this->string()->notNull(),
                'job' => $this->binary()->notNull(),
                'pushed_at' => $this->integer()->notNull(),
                'ttr' => $this->integer()->notNull(),
                'delay' => $this->integer()->notNull(),
                'priority' => $this->integer()->unsigned()->notNull()->defaultValue(1024),
                'reserved_at' => $this->integer(),
                'attempt' => $this->integer(),
                'done_at' => $this->integer(),
            ]);
            $this->createIndex('reserved_at', $this->tableName, 'reserved_at');
            $this->createIndex('priority', $this->tableName, 'priority');
        }
        $this->createIndex('channel', $this->tableName, 'channel');
    }
}
