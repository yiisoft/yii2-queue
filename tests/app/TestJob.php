<?php
/**
 * @link https://github.com/zhuravljov/yii2-queue
 * @copyright Copyright (c) 2017 Roman Zhuravlev
 * @license http://opensource.org/licenses/BSD-3-Clause
 */

namespace tests\app;

use Yii;
use yii\base\Object;
use zhuravljov\yii\queue\Job;

/**
 * Class Job
 *
 * @author Roman Zhuravlev <zhuravljov@gmail.com>
 */
class TestJob extends Object implements Job
{
    public $uid;

    public function execute($queue, $jobId)
    {
        file_put_contents($this->getFileName($jobId), '');
    }

    public function getFileName($jobId)
    {
        return Yii::getAlias("@runtime/job-{$this->uid}-{$jobId}.lock");
    }
}