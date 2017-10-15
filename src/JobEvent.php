<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\queue;

use yii\base\Event;

/**
 * Class JobEvent
 *
 * @author Roman Zhuravlev <zhuravljov@gmail.com>
 */
class JobEvent extends Event
{
    /**
     * @var string|null unique id of a job
     */
    public $id;
    /**
     * @var JobInterface
     */
    public $job;
    /**
     * @var int time to reserve in seconds of the job
     */
    public $ttr;
}