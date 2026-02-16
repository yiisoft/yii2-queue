<?php

/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

declare(strict_types=1);

namespace yii\queue\cli;

use yii\base\NotSupportedException;
use yii\console\Controller;
use yii\helpers\BaseConsole;
use yii\queue\interfaces\StatisticsProviderInterface;

/**
 * Info about queue status.
 *
 * @author Kalmer Kaurson <kalmerkaurson@gmail.com>
 *
 * @property Controller $controller
 */
class InfoAction extends Action
{
    /**
     * @var Queue
     */
    public Queue $queue;

    /**
     * Info about queue status.
     */
    public function run(): void
    {
        if (!($this->queue instanceof StatisticsProviderInterface)) {
            throw new NotSupportedException('Queue does not support ' . StatisticsProviderInterface::class);
        }

        $this->controller->stdout('Jobs' . PHP_EOL, BaseConsole::FG_GREEN);
        $statisticsProvider = $this->queue->getStatisticsProvider();

        $this->controller->stdout('- waiting: ', BaseConsole::FG_YELLOW);
        $this->controller->stdout($statisticsProvider->getWaitingCount() . PHP_EOL);

        $this->controller->stdout('- delayed: ', BaseConsole::FG_YELLOW);
        $this->controller->stdout($statisticsProvider->getDelayedCount() . PHP_EOL);

        $this->controller->stdout('- reserved: ', BaseConsole::FG_YELLOW);
        $this->controller->stdout($statisticsProvider->getReservedCount() . PHP_EOL);

        $this->controller->stdout('- done: ', BaseConsole::FG_YELLOW);
        $this->controller->stdout($statisticsProvider->getDoneCount() . PHP_EOL);
    }
}
