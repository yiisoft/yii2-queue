<?php

/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

declare(strict_types=1);

namespace tests\drivers\db;

use Yii;
use yii\queue\db\Queue;

/**
 * Postgres Queue Test.
 *
 * @author Roman Zhuravlev <zhuravljov@gmail.com>
 */
final class PgsqlQueueTest extends TestCase
{
    protected function getQueue(): Queue
    {
        return Yii::$app->pgsqlQueue;
    }
}
