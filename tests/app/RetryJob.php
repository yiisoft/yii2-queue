<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace tests\app;

use Yii;
use yii\base\BaseObject;
use yii\queue\RetryableJobInterface;

/**
 * Retry Job.
 *
 * @author Roman Zhuravlev <zhuravljov@gmail.com>
 */
class RetryJob extends BaseObject implements RetryableJobInterface
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
