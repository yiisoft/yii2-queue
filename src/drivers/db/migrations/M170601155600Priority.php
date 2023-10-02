<?php

declare(strict_types=1);

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
class M170601155600Priority extends Migration
{
    public string $tableName = '{{%queue}}';

    public function up(): void
    {
        $this->addColumn(
            $this->tableName,
            'priority',
            $this->integer()->unsigned()->notNull()->defaultValue(1024)->after('delay')
        );
        $this->createIndex('priority', $this->tableName, 'priority');
    }

    public function down(): void
    {
        $this->dropIndex('priority', $this->tableName);
        $this->dropColumn($this->tableName, 'priority');
    }
}
