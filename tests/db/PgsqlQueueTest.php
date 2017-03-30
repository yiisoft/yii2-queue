<?php
/**
 * @link https://github.com/zhuravljov/yii2-queue
 * @copyright Copyright (c) 2017 Roman Zhuravlev
 * @license http://opensource.org/licenses/BSD-3-Clause
 */

namespace tests\db;

use Yii;
use zhuravljov\yii\queue\db\Queue;

/**
 * Postgres Queue Test
 *
 * @author Roman Zhuravlev <zhuravljov@gmail.com>
 */
class PgsqlQueueTest extends QueueTestCase
{
    /**
     * @return Queue
     */
    protected function getQueue()
    {
        return Yii::$app->pgsqlQueue;
    }

    public function setUp()
    {
        if (defined('HHVM_VERSION')) {
            $this->markTestSkipped();
        } else {
            parent::setUp();
        }
    }
}