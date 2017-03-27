<?php
/**
 * @link https://github.com/zhuravljov/yii2-queue
 * @copyright Copyright (c) 2017 Roman Zhuravlev
 * @license http://opensource.org/licenses/BSD-3-Clause
 */

namespace tests\db;

use Yii;

/**
 * Sqlite Driver Test
 *
 * @author Roman Zhuravlev <zhuravljov@gmail.com>
 */
class SqliteDriverTest extends DriverTestCase
{
    protected function getQueue()
    {
        return Yii::$app->sqliteQueue;
    }
}