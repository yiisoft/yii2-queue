<?php
/**
 * @link https://github.com/zhuravljov/yii2-queue
 * @copyright Copyright (c) 2017 Roman Zhuravlev
 * @license http://opensource.org/licenses/BSD-3-Clause
 */

namespace zhuravljov\yii\queue;

/**
 * Interface RetryableJob
 *
 * @author Roman Zhuravlev <zhuravljov@gmail.com>
 */
interface RetryableJob extends Job
{
    /**
     * @return int time to reserve in seconds
     */
    public function getTtr();

    /**
     * @param int $attempt number
     * @param \Exception $error from last execute of the job
     * @return bool
     */
    public function canRetry($attempt, $error);
}