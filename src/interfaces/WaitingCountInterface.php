<?php
/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

namespace yii\queue\interfaces;

/**
 * Waiting Count Interface
 *
 * @author Kalmer Kaurson <kalmerkaurson@gmail.com>
 */
interface WaitingCountInterface
{
    /**
     * @return int
     */
    public function getWaitingCount();
}
