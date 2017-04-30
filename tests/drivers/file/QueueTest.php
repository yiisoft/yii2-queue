<?php
/**
 * @link https://github.com/zhuravljov/yii2-queue
 * @copyright Copyright (c) 2017 Roman Zhuravlev
 * @license http://opensource.org/licenses/BSD-3-Clause
 */

namespace tests\drivers\file;

use tests\drivers\CliTestCase;
use Yii;
use zhuravljov\yii\queue\drivers\file\Queue;

/**
 * File Queue Test
 *
 * @author Roman Zhuravlev <zhuravljov@gmail.com>
 */
class QueueTest extends CliTestCase
{
    /**
     * @return Queue
     */
    protected function getQueue()
    {
        return Yii::$app->fileQueue;
    }

    protected function tearDown()
    {
        foreach (glob(Yii::getAlias("@runtime/queue/*")) as $fileName) {
            unlink($fileName);
        }
        parent::tearDown();
    }
}