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
class M170307170300Later extends Migration
{
    public string $tableName = '{{%queue}}';

    public function up(): void
    {
        $this->addColumn(
            $this->tableName,
            'timeout',
            $this->integer()->defaultValue(0)->notNull()->after('created_at')
        );
    }

    public function down(): void
    {
        $this->dropColumn($this->tableName, 'timeout');
    }
}
