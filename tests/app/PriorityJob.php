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
 * Class PriorityJob
 *
 * @author Roman Zhuravlev <zhuravljov@gmail.com>
 */
class PriorityJob extends Object implements Job
{
    public $number;

    public function execute($queue)
    {
        file_put_contents(self::getFileName(), $this->number, FILE_APPEND);
    }

    public static function getFileName()
    {
        return Yii::getAlias("@runtime/job-priority.log");
    }
}