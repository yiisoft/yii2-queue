<?php

declare(strict_types=1);

/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

namespace tests\app;

use Yii;
use yii\base\BaseObject;
use yii\queue\JobInterface;
use yii\queue\Queue;

/**
 * Simple Job.
 *
 * @author Roman Zhuravlev <zhuravljov@gmail.com>
 */
class SimpleJob extends BaseObject implements JobInterface
{
    public string $uid;

    public function execute(Queue $queue): void
    {
        file_put_contents($this->getFileName(), '');
    }

    public function getFileName(): bool|string
    {
        return Yii::getAlias("@runtime/job-{$this->uid}.lock");
    }
}
