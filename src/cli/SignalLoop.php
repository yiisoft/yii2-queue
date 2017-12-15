<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\queue\cli;

use yii\base\BaseObject;

/**
 * Class SignalLoop
 *
 * @author Roman Zhuravlev <zhuravljov@gmail.com>
 * @since 2.0.2
 */
class SignalLoop extends BaseObject implements LoopInterface
{
    /**
     * @var array of signals that must stop listening of the queue.
     */
    public $stopSignals = [SIGTERM, SIGINT, SIGHUP];

    private static $stopped = false;
    private $handled = false;

    /**
     * @inheritdoc
     */
    public function canContinue()
    {
        if (static::$stopped) {
            return false;
        }

        if (function_exists('pcntl_signal')) {
            if (!$this->handled) {
                foreach ($this->stopSignals as $signal) {
                    pcntl_signal($signal, function () {
                        static::$stopped = true;
                    });
                }
                $this->handled = true;
            }
            pcntl_signal_dispatch();
        }

        return !static::$stopped;
    }
}