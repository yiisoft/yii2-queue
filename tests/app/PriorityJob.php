<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace tests\app;

use Yii;
use yii\base\BaseObject;
use yii\queue\JobInterface;

/**
 * Priority Job.
 *
 * @author Roman Zhuravlev <zhuravljov@gmail.com>
 */
class PriorityJob extends BaseObject implements JobInterface
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
