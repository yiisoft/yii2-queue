<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\queue\cli;

/**
 * Process Signal Helper
 *
 * @author Roman Zhuravlev <zhuravljov@gmail.com>
 */
class Signal
{
    private static $exit = false;


    /**
     * Checks exit signals
     * Used mainly by [[yii\queue\Queue]] to check, whether job execution
     * loop can be continued.
     * @return bool
     */
    public static function isExit()
    {
        if (function_exists('pcntl_signal')) {
            // Installs a signal handler
            static $handled = false;
            if (!$handled) {
                foreach ([SIGTERM, SIGINT, SIGHUP] as $signal) {
                    pcntl_signal($signal, function () {
                        static::setExitFlag();
                    });
                }
                $handled = true;
            }

            // Checks signal
            if (!static::$exit) {
                pcntl_signal_dispatch();
            }
        }

        return static::$exit;
    }

    /**
     * Sets exit flag to `true`
     * Method can be used to simulate exit signal for methods that use
     * [[isExit()]] to check whether execution loop can be continued.
     */
    public static function setExitFlag()
    {
        static::$exit = true;
    }
}
