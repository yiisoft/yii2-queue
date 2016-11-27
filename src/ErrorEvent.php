<?php

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