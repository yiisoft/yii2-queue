<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace tests\benchmark\waiting;

use yii\base\Object;
use yii\queue\JobInterface;

/**
 * Class Job
 *
 * @author Roman Zhuravlev <zhuravljov@gmail.com>
 */
class Job extends Object implements JobInterface
{
    public $pushedAt;
    public $resultFileName;
    public $lockFileName;

    public function execute($queue)
    {
        $waitingTime = microtime(true) - $this->pushedAt;
        if (file_exists($this->lockFileName)) {
            file_put_contents($this->resultFileName, "$waitingTime\n", FILE_APPEND);
            usleep(rand(100000, 300000));
            unlink($this->lockFileName);
        }
    }
}