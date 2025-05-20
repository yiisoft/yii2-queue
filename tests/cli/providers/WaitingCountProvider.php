<?php

declare(strict_types=1);

/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

namespace tests\cli\providers;

use yii\queue\interfaces\WaitingCountInterface;

/**
 * Waiting Count Provider
 *
 * @author Kalmer Kaurson <kalmerkaurson@gmail.com>
 */
class WaitingCountProvider extends BaseStatisticsProvider implements WaitingCountInterface
{
    /**
     * @inheritdoc
     */
    public function getWaitingCount(): int
    {
        return 10;
    }
}
