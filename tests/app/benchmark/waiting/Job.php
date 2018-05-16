<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace tests\app\benchmark\waiting;

use yii\base\BaseObject;
use yii\queue\JobInterface;

/**
 * The job calculates waiting time.
 *
 * @author Roman Zhuravlev <zhuravljov@gmail.com>
 */
class Job extends BaseObject implements JobInterface
{
    public $pushedAt;
    public $resultFileName;
    public $lockFileName;
    public $payload;

    public function execute($queue)
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
