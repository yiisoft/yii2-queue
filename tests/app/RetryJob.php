<?php

declare(strict_types=1);

/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

namespace tests\app;

use Exception;
use Yii;
use yii\base\BaseObject;
use yii\queue\Queue;
use yii\queue\RetryableJobInterface;

/**
 * Retry Job.
 *
 * @author Roman Zhuravlev <zhuravljov@gmail.com>
 */
class RetryJob extends BaseObject implements RetryableJobInterface
{
    public string $uid;

    public function execute(Queue $queue): void
    {
        file_put_contents($this->getFileName(), 'a', FILE_APPEND);
        throw new Exception('Planned error.');
    }

    public function getFileName(): bool|string
    {
        return Yii::getAlias("@runtime/job-$this->uid.lock");
    }

    public function getTtr(): int
    {
        return 2;
    }

    public function canRetry($attempt, $error): bool
    {
        return $attempt < 2;
    }
}
