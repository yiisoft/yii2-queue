<?php

/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

namespace tests\cli\providers;

use yii\queue\interfaces\ReservedCountInterface;

/**
 * Reserved Count Provider
 *
 * @author Kalmer Kaurson <kalmerkaurson@gmail.com>
 */
class ReservedCountProvider extends BaseStatisticsProvider implements ReservedCountInterface
{
    /**
     * @inheritdoc
     */
    public function getReservedCount()
    {
        return 10;
    }
}
