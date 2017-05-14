<?php
/**
 * @link https://github.com/zhuravljov/yii2-queue
 * @copyright Copyright (c) 2017 Roman Zhuravlev
 * @license http://opensource.org/licenses/BSD-3-Clause
 */

namespace zhuravljov\yii\queue;

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
     * @var Job
     */
    public $job;
    /**
     * @var int time to reserve in seconds of the job
     */
    public $ttr;
}