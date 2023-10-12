<?php

declare(strict_types=1);

/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

namespace yii\queue;

use Closure;
use yii\base\Event;

/**
 * Job Event.
 *
 * @author Roman Zhuravlev <zhuravljov@gmail.com>
 */
abstract class JobEvent extends Event
{
    /**
     * @var Queue
     * @inheritdoc
     */
    public $sender;
    /**
     * @var int|string|null unique id of a job
     */
    public string|int|null $id;
    /**
     * @var Closure|JobInterface|null|mixed
     */
    public mixed $job;
    /**
     * @var int time to reserve in seconds of the job
     */
    public int $ttr;
}
