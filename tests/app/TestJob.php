<?php

namespace tests\app;

use yii\base\Object;
use zhuravljov\yii\queue\Job;

/**
 * Class Job
 *
 * @author Roman Zhuravlev <zhuravljov@gmail.com>
 */
class TestJob extends Object implements Job
{
    public $fileName;

    public function run()
    {
        file_put_contents($this->fileName, time());
    }
}