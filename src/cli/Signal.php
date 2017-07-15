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
                        static::$exit = true;
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
}