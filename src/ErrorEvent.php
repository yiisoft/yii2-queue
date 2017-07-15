<?php
/**
 * @link https://github.com/zhuravljov/yii2-queue
 * @copyright Copyright (c) 2017 Roman Zhuravlev
 * @license http://opensource.org/licenses/BSD-3-Clause
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
     * @var \Exception
     */
    public $error;
    /**
     * @var bool
     */
    public $retry;
}