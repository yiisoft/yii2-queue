<?php

/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

declare(strict_types=1);

namespace yii\queue\interfaces;

/**
 * Reserved Count Interface
 *
 * @author Kalmer Kaurson <kalmerkaurson@gmail.com>
 */
interface ReservedCountInterface
{
    /**
     * @return int
     */
    public function getReservedCount(): int;
}
