<?php

namespace zhuravljov\yii\queue;

/**
 * Class Event
 *
 * @author Roman Zhuravlev <zhuravljov@gmail.com>
 */
class Event extends \yii\base\Event
{
    /**
     * @var Job $job
     */
    public $job;
}