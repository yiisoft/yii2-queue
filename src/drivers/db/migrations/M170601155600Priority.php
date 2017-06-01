<?php
/**
 * @link https://github.com/zhuravljov/yii2-queue
 * @copyright Copyright (c) 2017 Roman Zhuravlev
 * @license http://opensource.org/licenses/BSD-3-Clause
 */

namespace zhuravljov\yii\queue\db\migrations;

use yii\db\Migration;

/**
 * Example of migration for queue message storage
 *
 * @author Roman Zhuravlev <zhuravljov@gmail.com>
 */
class M170601155600Priority extends Migration
{
    public $tableName = '{{%queue}}';

    public function up()
    {
        $this->addColumn($this->tableName, 'priority', $this->integer()->unsigned()->notNull()->defaultValue(1024)->after('delay'));
        $this->createIndex('priority', $this->tableName, 'priority');
    }

    public function down()
    {
        $this->dropIndex('priority', $this->tableName);
        $this->dropColumn($this->tableName, 'priority');
    }
}
