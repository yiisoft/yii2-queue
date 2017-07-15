<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace tests\drivers\file;

use tests\drivers\CliTestCase;
use Yii;
use yii\queue\file\Queue;

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