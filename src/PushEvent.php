<?php
/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

namespace yii\queue;

/**
 * Push Event.
 *
 * @author Roman Zhuravlev <zhuravljov@gmail.com>
 */
class PushEvent extends JobEvent
{
    /**
     * @var int
     */
    public $delay;
    /**
     * @var mixed
     */
    public $priority;
}
