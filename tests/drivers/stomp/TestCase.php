<?php

declare(strict_types=1);

/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

namespace tests\drivers\stomp;

use tests\drivers\CliTestCase;
use Yii;
use yii\queue\stomp\Queue;

class TestCase extends CliTestCase
{
    /**
     * @return Queue
     */
    protected function getQueue(): Queue
    {
        return Yii::$app->stompQueue;
    }
}
