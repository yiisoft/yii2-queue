<?php

/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

declare(strict_types=1);

namespace tests\cli\providers;

use tests\cli\Queue;
use yii\base\BaseObject;
use yii\queue\interfaces\StatisticsInterface;

/**
 * Statistics Provider
 *
 * @author Kalmer Kaurson <kalmerkaurson@gmail.com>
 */
class BaseStatisticsProvider extends BaseObject implements StatisticsInterface
{
    /**
     * @var Queue
     */
    protected Queue $queue;

    public function __construct(Queue $queue, array $config = [])
    {
        $this->queue = $queue;
        parent::__construct($config);
    }
}
