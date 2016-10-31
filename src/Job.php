<?php

namespace zhuravljov\yii\queue;

/**
 * Interface Job
 *
 * @author Roman Zhuravlev <zhuravljov@gmail.com>
 */
interface Job
{
    /**
     * @param Queue $queue
     */
    public function run($queue);
}