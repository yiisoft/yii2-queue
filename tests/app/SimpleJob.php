<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace tests\app;

use Yii;
use yii\base\Object;
use yii\queue\Job;

/**
 * Class SimpleJob
 *
 * @author Roman Zhuravlev <zhuravljov@gmail.com>
 */
class SimpleJob extends Object implements Job
{
    public $uid;

    public function execute($queue)
    {
        file_put_contents($this->getFileName(), '');
    }

    public function getFileName()
    {
        return Yii::getAlias("@runtime/job-{$this->uid}.lock");
    }
}