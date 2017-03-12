<?php

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

    public function run()
    {
        file_put_contents($this->getFileName(), '');
    }

    public function getFileName()
    {
        return Yii::getAlias("@runtime/job-{$this->uid}.lock");
    }
}