<?php

/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

declare(strict_types=1);

namespace yii\queue\interfaces;

/**
 * Statistics Provider Interface
 *
 * @author Kalmer Kaurson <kalmerkaurson@gmail.com>
 */
interface StatisticsProviderInterface
{
    /**
     * @return StatisticsInterface
     */
    public function getStatisticsProvider(): StatisticsInterface;
}
