<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\queue\cli;

use yii\base\Event;

/**
 * Class WorkerEvent
 *
 * @author Roman Zhuravlev <zhuravljov@gmail.com>
 * @since 2.0.2
 */
class WorkerEvent extends Event
{
    /**
     * @var \yii\base\Action
     */
    public $action;
    /**
     * @var int pid of the worker
     */
    public $pid;
}
