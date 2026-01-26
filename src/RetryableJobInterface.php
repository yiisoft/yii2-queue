<?php

/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

declare(strict_types=1);

namespace yii\queue;

use Throwable;

/**
 * Retryable Job Interface.
 *
 * @author Roman Zhuravlev <zhuravljov@gmail.com>
 */
interface RetryableJobInterface extends JobInterface
{
    /**
     * @return int time to reserve in seconds
     */
    public function getTtr(): int;

    /**
     * @param int $attempt number
     * @param Throwable|null $error from last execute of the job
     * @return bool
     */
    public function canRetry(int $attempt, ?Throwable $error): bool;
}
