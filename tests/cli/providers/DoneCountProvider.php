<?php

/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

namespace tests\cli\providers;

use yii\queue\interfaces\DoneCountInterface;

/**
 * Done Count Provider
 *
 * @author Kalmer Kaurson <kalmerkaurson@gmail.com>
 */
class DoneCountProvider extends BaseStatisticsProvider implements DoneCountInterface
{
    /**
     * @inheritdoc
     */
    public function getDoneCount()
    {
        return 10;
    }
}
