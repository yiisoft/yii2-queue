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