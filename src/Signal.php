<?php
/**
 * @link https://github.com/zhuravljov/yii2-queue
 * @copyright Copyright (c) 2017 Roman Zhuravlev
 * @license http://opensource.org/licenses/BSD-3-Clause
 */

namespace zhuravljov\yii\queue;

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