<?php

declare(strict_types=1);

/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

namespace tests\cli;

use tests\cli\providers\BaseStatisticsProvider;
use yii\base\NotSupportedException;
use yii\queue\cli\Queue as CliQueue;
use yii\queue\interfaces\StatisticsInterface;
use yii\queue\interfaces\StatisticsProviderInterface;

/**
 * test Queue.
 *
 * @author Kalmer Kaurson <kalmerkaurson@gmail.com>
 */
class Queue extends CliQueue implements StatisticsProviderInterface
{
    /**
     * @inheritdoc
     */
    public function status($id): int
    {
        throw new NotSupportedException('"status" method is not supported.');
    }
    /**
     * @inheritdoc
     */
    protected function pushMessage(string $payload, $ttr, $delay, $priority): int|string|null
    {
        throw new NotSupportedException('"pushMessage" method is not supported.');
    }

    private StatisticsInterface $_statisticsProvider;

    /**
     * @return StatisticsInterface
     */
    public function getStatisticsProvider(): StatisticsInterface
    {
        if (!isset($this->_statisticsProvider)) {
            $this->_statisticsProvider = new BaseStatisticsProvider($this);
        }
        return $this->_statisticsProvider;
    }
}
