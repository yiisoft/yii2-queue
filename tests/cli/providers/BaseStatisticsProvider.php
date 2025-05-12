<?php

/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

namespace tests\cli\providers;

use tests\cli\Queue;
use yii\base\BaseObject;

/**
 * Statistics Provider
 *
 * @author Kalmer Kaurson <kalmerkaurson@gmail.com>
 */
class BaseStatisticsProvider extends BaseObject
{
    /**
     * @var Queue
     */
    protected $queue;

    public function __construct(Queue $queue, $config = [])
    {
        $this->queue = $queue;
        parent::__construct($config);
    }
}
