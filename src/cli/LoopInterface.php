<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\queue\cli;

/**
 * Loop Interface
 *
 * @author Roman Zhuravlev <zhuravljov@gmail.com>
 * @since 2.0.2
 */
interface LoopInterface
{
    /**
     * @return bool whether to continue listening of the queue.
     */
    public function canContinue();
}