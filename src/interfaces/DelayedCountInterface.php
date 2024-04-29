<?php
/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

namespace yii\queue\interfaces;

/**
 * Delayed Count Interface
 *
 * @author Kalmer Kaurson <kalmerkaurson@gmail.com>
 */
interface DelayedCountInterface
{
    /**
     * @return int
     */
    public function getDelayedCount();
}
