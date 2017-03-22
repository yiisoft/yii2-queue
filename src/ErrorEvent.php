<?php
/**
 * @link https://github.com/zhuravljov/yii2-queue
 * @copyright Copyright (c) 2017 Roman Zhuravlev
 * @license http://opensource.org/licenses/BSD-3-Clause
 */

namespace zhuravljov\yii\queue;

/**
 * Class ErrorEvent
 *
 * @author Roman Zhuravlev <zhuravljov@gmail.com>
 */
class ErrorEvent extends JobEvent
{
    /**
     * @var \Exception
     */
    public $error;
}