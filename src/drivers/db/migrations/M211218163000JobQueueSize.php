<?php
/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

namespace yii\queue\db\migrations;

use yii\db\Migration;

/**
 * Example of migration for queue message storage.
 *
 * @author Roman Zhuravlev <zhuravljov@gmail.com>
 */
class M211218163000JobQueueSize extends Migration
{
    public $tableName = '{{%queue}}';


    public function up()
    {
        if ($this->db->driverName === 'mysql') {
            $this->alterColumn('{{%queue}}', 'job', 'LONGBLOB NOT NULL');
        }
    }

    public function down()
    {
        if ($this->db->driverName === 'mysql') {
            $this->alterColumn('{{%queue}}', 'job', $this->binary()->notNull());
        }
    }
}
