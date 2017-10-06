<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\queue;

/**
 * Interface JobInterface
 *
 * @author Roman Zhuravlev <zhuravljov@gmail.com>
 */
interface JobInterface
{
    /**
     * @param Queue $queue which pushed and is handling the job
     */
    public function execute($queue);
}