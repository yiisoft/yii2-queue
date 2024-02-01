<?php

/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

namespace tests\cli\providers;

use yii\queue\interfaces\DelayedCountInterface;

/**
 * Delayed Count Provider
 *
 * @author Kalmer Kaurson <kalmerkaurson@gmail.com>
 */
class DelayedCountProvider extends BaseStatisticsProvider implements DelayedCountInterface
{
    /**
     * @inheritdoc
     */
    public function getDelayedCount()
    {
        return 10;
    }
}
