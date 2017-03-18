<?php

namespace zhuravljov\yii\queue\db\migrations;

use yii\db\Migration;

/**
 * Example of migration for queue message storage
 *
 * @author Roman Zhuravlev <zhuravljov@gmail.com>
 */
class M170307170300Later extends Migration
{
    public $tableName = '{{%queue}}';

    public function up()
    {
        $this->addColumn($this->tableName, 'timeout', $this->integer()->notNull()->after('created_at'));
    }

    public function down()
    {
        $this->dropColumn($this->tableName, 'timeout');
    }
}
