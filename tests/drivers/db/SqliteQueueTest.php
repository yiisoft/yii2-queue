<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace tests\drivers\db;

use Yii;
use yii\queue\db\Queue;

/**
 * Sqlite Queue Test.
 *
 * @author Roman Zhuravlev <zhuravljov@gmail.com>
 */
class SqliteQueueTest extends TestCase
{
    /**
     * @return Queue
     */
    protected function getQueue()
    {
        return Yii::$app->sqliteQueue;
    }
}
