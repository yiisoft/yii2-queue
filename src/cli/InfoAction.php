<?php

declare(strict_types=1);

/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

namespace yii\queue\cli;

use yii\base\NotSupportedException;
use yii\console\Controller;
use yii\helpers\Console;
use yii\queue\interfaces\DelayedCountInterface;
use yii\queue\interfaces\DoneCountInterface;
use yii\queue\interfaces\ReservedCountInterface;
use yii\queue\interfaces\StatisticsProviderInterface;
use yii\queue\interfaces\WaitingCountInterface;

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

        $this->controller->stdout('Jobs' . PHP_EOL, Console::FG_GREEN);
        $statisticsProvider = $this->queue->getStatisticsProvider();

        if ($statisticsProvider instanceof WaitingCountInterface) {
            $this->controller->stdout('- waiting: ', Console::FG_YELLOW);
            $this->controller->stdout($statisticsProvider->getWaitingCount() . PHP_EOL);
        }

        if ($statisticsProvider instanceof DelayedCountInterface) {
            $this->controller->stdout('- delayed: ', Console::FG_YELLOW);
            $this->controller->stdout($statisticsProvider->getDelayedCount() . PHP_EOL);
        }

        if ($statisticsProvider instanceof ReservedCountInterface) {
            $this->controller->stdout('- reserved: ', Console::FG_YELLOW);
            $this->controller->stdout($statisticsProvider->getReservedCount() . PHP_EOL);
        }

        if ($statisticsProvider instanceof DoneCountInterface) {
            $this->controller->stdout('- done: ', Console::FG_YELLOW);
            $this->controller->stdout($statisticsProvider->getDoneCount() . PHP_EOL);
        }
    }
}
