<?php
/**
 * @link https://github.com/zhuravljov/yii2-queue
 * @copyright Copyright (c) 2017 Roman Zhuravlev
 * @license http://opensource.org/licenses/BSD-3-Clause
 */

namespace tests\app;

use Yii;
use yii\base\Object;
use yii\queue\RetryableJob;

/**
 * Class RetryJob
 *
 * @author Roman Zhuravlev <zhuravljov@gmail.com>
 */
class RetryJob extends Object implements RetryableJob
{
    public $uid;

    public function execute($queue)
    {
        file_put_contents($this->getFileName(), 'a', FILE_APPEND);
        throw new \Exception('Planned error.');
    }

    public function getFileName()
    {
        return Yii::getAlias("@runtime/job-{$this->uid}.lock");
    }

    public function getTtr()
    {
        return 2;
    }

    public function canRetry($attempt, $error)
    {
        return $attempt < 2;
    }
}