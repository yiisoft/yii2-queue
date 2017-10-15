<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\queue;

/**
 * Class ErrorEvent
 *
 * @author Roman Zhuravlev <zhuravljov@gmail.com>
 */
class ErrorEvent extends ExecEvent
{
    /**
     * @var \Exception|\Throwable
     */
    public $error;
    /**
     * @var bool
     */
    public $retry;
}