<?php

declare(strict_types=1);

/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

namespace yii\queue\cli;

use yii\base\BaseObject;

/**
 * Signal Loop.
 *
 * @author Roman Zhuravlev <zhuravljov@gmail.com>
 * @since 2.0.2
 */
class SignalLoop extends BaseObject implements LoopInterface
{
    /**
     * @var array of signals to exit from listening of the queue.
     */
    public array $exitSignals = [
        15, // SIGTERM
        3,  // SIGQUIT
        2,  // SIGINT
        1,  // SIGHUP
    ];
    /**
     * @var array of signals to suspend listening of the queue.
     * For example: SIGTSTP
     */
    public array $suspendSignals = [];
    /**
     * @var array of signals to resume listening of the queue.
     * For example: SIGCONT
     */
    public array $resumeSignals = [];

    /**
     * @var Queue
     */
    protected Queue $queue;

    /**
     * @var bool status when exit signal was got.
     */
    private static bool $exit = false;
    /**
     * @var bool status when suspend or resume signal was got.
     */
    private static bool $pause = false;

    /**
     * @param Queue $queue
     * @inheritdoc
     */
    public function __construct($queue, array $config = [])
    {
        $this->queue = $queue;
        parent::__construct($config);
    }

    /**
     * Sets signal handlers.
     *
     * @inheritdoc
     */
    public function init(): void
    {
        parent::init();
        if (extension_loaded('pcntl') && function_exists('pcntl_signal')) {
            foreach ($this->exitSignals as $signal) {
                pcntl_signal($signal, static function () {
                    self::$exit = true;
                });
            }
            foreach ($this->suspendSignals as $signal) {
                pcntl_signal($signal, static function () {
                    self::$pause = true;
                });
            }
            foreach ($this->resumeSignals as $signal) {
                pcntl_signal($signal, static function () {
                    self::$pause = false;
                });
            }
        }
    }

    /**
     * Checks signals state.
     *
     * @inheritdoc
     */
    public function canContinue(): bool
    {
        if (extension_loaded('pcntl') && function_exists('pcntl_signal_dispatch')) {
            pcntl_signal_dispatch();
            // Wait for resume signal until loop is suspended
            while (self::$pause && !self::$exit) {
                usleep(10000);
                pcntl_signal_dispatch();
            }
        }

        return !self::$exit;
    }
}
