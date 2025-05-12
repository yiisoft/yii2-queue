<?php
/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

namespace tests\cli;

use tests\cli\providers\BaseStatisticsProvider;
use yii\base\NotSupportedException;
use yii\queue\cli\Queue as CliQueue;
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
    public function status($id)
    {
        throw new NotSupportedException('"status" method is not supported.');
    }
    /**
     * @inheritdoc
     */
    protected function pushMessage($message, $ttr, $delay, $priority)
    {
        throw new NotSupportedException('"pushMessage" method is not supported.');
    }

    /**
     * @return StatisticsProvider
     */
    public function getStatisticsProvider()
    {
        if (!$this->_statistcsProvider) {
            $this->_statistcsProvider = new BaseStatisticsProvider($this);
        }
        return $this->_statistcsProvider;
    }
}
