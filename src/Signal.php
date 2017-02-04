<?php

namespace zhuravljov\yii\queue;

/**
 * Process Signal Helper
 *
 * @author Roman Zhuravlev <zhuravljov@gmail.com>
 */
class Signal
{
    private static $term = false;

    /**
     * Checks SIGTERM signal
     * @return bool
     */
    public static function isTerm()
    {
        if (function_exists('pcntl_signal')) {
            // Installs a signal handler
            static $handled = false;
            if (!$handled) {
                pcntl_signal(SIGTERM, function () {
                    static::$term = true;
                });
                $handled = true;
            }
            // Checks signal
            if (!static::$term) {
                pcntl_signal_dispatch();
            }
        }
        return static::$term;
    }
}