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
     * @var array of signals to exit from listening of the queue.
     */
    public $exitSignals = [SIGTERM, SIGINT, SIGHUP];
    /**
     * @var array of signals to suspend listening of the queue.
     * For example: SIGTSTP
     */
    public $suspendSignals = [];
    /**
     * @var array of signals to resume listening of the queue.
     * For example: SIGCONT
     */
    public $resumeSignals = [];
    /**
     * @var bool status when exit signal was got.
     */
    private static $exit = false;
    /**
     * @var bool status when suspend or resume signal was got.
     */
    private static $pause = false;

    private $handled = false;

    /**
     * Initializes signal handlers once and checks state its.
     * @inheritdoc
     */
    public function canContinue()
    {
        if (self::$exit) {
            return false;
        }

        if (extension_loaded('pcntl')) {
            $this->initHandlers();
            $this->updateStatus();
        }

        return !self::$exit;
    }

    /**
     * Sets signal handlers
     */
    private function initHandlers()
    {
        if ($this->handled) {
            return;
        }

        foreach ($this->exitSignals as $signal) {
            pcntl_signal($signal, function () {
                self::$exit = true;
            });
        }
        foreach ($this->suspendSignals as $signal) {
            pcntl_signal($signal, function () {
                self::$pause = true;
            });
        }
        foreach ($this->resumeSignals as $signal) {
            pcntl_signal($signal, function () {
                self::$pause = false;
            });
        }

        $this->handled = true;
    }

    /**
     * Checks signals and updates status.
     */
    private function updateStatus()
    {
        pcntl_signal_dispatch();
        // Wait for resume signal until loop is suspended
        while (self::$pause && !self::$exit) {
            usleep(10000);
            pcntl_signal_dispatch();
        }
    }
}