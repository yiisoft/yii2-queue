<?php

declare(strict_types=1);

/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

namespace tests\app\benchmark\waiting;

use yii\base\BaseObject;
use yii\queue\JobInterface;
use yii\queue\Queue;

/**
 * The job calculates waiting time.
 *
 * @author Roman Zhuravlev <zhuravljov@gmail.com>
 */
class Job extends BaseObject implements JobInterface
{
    public float $pushedAt;
    public string $resultFileName;
    public string $lockFileName;
    public string $payload;

    public function execute(Queue $queue): void
    {
        $waitingTime = microtime(true) - $this->pushedAt;
        if (file_exists($this->lockFileName)) {
            // Saves waiting time to storage
            file_put_contents($this->resultFileName, "$waitingTime\n", FILE_APPEND | LOCK_EX);
            // Emulation of job execution
            usleep(rand(100000, 300000));
            // Signals to the benchmark that job is done
            unlink($this->lockFileName);
        }
    }
}
