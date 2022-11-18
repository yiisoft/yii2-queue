<?php
/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

namespace yii\queue\cli;

use yii\base\Event;

/**
 * Worker Event.
 *
 * @author Roman Zhuravlev <zhuravljov@gmail.com>
 * @since 2.0.2
 */
class WorkerEvent extends Event
{
    /**
     * @var Queue
     * @inheritdoc
     */
    public $sender;
    /**
     * @var LoopInterface
     */
    public $loop;
    /**
     * @var null|int exit code
     */
    public $exitCode;
}
