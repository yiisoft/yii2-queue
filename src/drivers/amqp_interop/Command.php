<?php

/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

declare(strict_types=1);

namespace yii\queue\amqp_interop;

use yii\queue\cli\Command as CliCommand;
use yii\queue\cli\Queue as CliQueue;

/**
 * Manages application amqp-queue.
 *
 * @author Maksym Kotliar <kotlyar.maksim@gmail.com>
 * @since 2.0.2
 */
class Command extends CliCommand
{
    /**
     * @var Queue
     */
    public CliQueue $queue;

    /**
     * @inheritdoc
     */
    public function init(): void
    {
        parent::init();

        // https://github.com/yiisoft/yii2-queue/issues/379
        if (PHP_MAJOR_VERSION >= 7 && extension_loaded('pcntl') && function_exists('pcntl_signal')) {
            // https://github.com/php-amqplib/php-amqplib#unix-signals
            $signals = [SIGTERM, SIGQUIT, SIGINT, SIGHUP];

            foreach ($signals as $signal) {
                $oldHandler = null;
                // This got added in php 7.1 and might not exist on all supported versions
                if (function_exists('pcntl_signal_get_handler')) {
                    $oldHandler = pcntl_signal_get_handler($signal);
                }

                pcntl_signal($signal, static function ($signal) use ($oldHandler) {
                    if ($oldHandler && is_callable($oldHandler)) {
                        $oldHandler($signal);
                    }

                    pcntl_signal($signal, SIG_DFL);
                    posix_kill(posix_getpid(), $signal);
                });
            }
        }
    }

    /**
     * @inheritdoc
     */
    protected function isWorkerAction($actionID): bool
    {
        return $actionID === 'listen';
    }

    /**
     * Listens amqp-queue and runs new jobs.
     * It can be used as daemon process.
     */
    public function actionListen(): void
    {
        $this->queue->listen();
    }
}
