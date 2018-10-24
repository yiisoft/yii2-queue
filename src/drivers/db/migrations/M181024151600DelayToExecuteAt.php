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
        $this->addColumn($this->tableName, 'execute_at', $this->integer()->notNull()->after('ttr'));
        $this->update($this->tableName, [
            'execute_at' => new Expression('[[pushed_at]] + [[delay]]')
        ]);
        $this->dropColumn($this->tableName, 'delay');
        $this->createIndex('idx_queue__channel__execute_at', $this->tableName, ['channel', 'execute_at']);
    }

    public function down()
    {
        $this->dropIndex('idx_queue__channel__execute_at', $this->tableName);
        $this->addColumn($this->tableName, 'delay', $this->integer()->notNull()->after('ttr'));
        $this->update($this->tableName, [
            'delay' => new Expression('[[execute_at]] - [[pushed_at]]')
        ]);
        $this->dropColumn($this->tableName, 'execute_at');
    }
}
