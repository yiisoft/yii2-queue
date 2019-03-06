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
 * Deadline Job.
 *
 * @author Fedor Andreev <fedorka96@ya.ru>
 */
class DeadlineJob extends BaseObject implements JobInterface
{
    public $deadline;
    public $fileName;

    public function execute($queue)
    {
        file_put_contents($this->fileName, 'a', FILE_APPEND);
        usleep(max(0, intval(1000000 * ($this->deadline - microtime(true)))));
    }
}
