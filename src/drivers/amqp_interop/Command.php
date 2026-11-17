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
        if (extension_loaded('pcntl') && function_exists('pcntl_signal')) {
            // https://github.com/php-amqplib/php-amqplib#unix-signals
            $signals = [SIGTERM, SIGQUIT, SIGINT, SIGHUP];

            foreach ($signals as $signal) {
                $oldHandler = pcntl_signal_get_handler($signal);

                pcntl_signal($signal, static function (int $signal) use ($oldHandler): void {
                    if (self::isSignalHandler($oldHandler)) {
                        $oldHandler($signal);
                    }

                    pcntl_signal($signal, SIG_DFL);
                    posix_kill(posix_getpid(), $signal);
                });
            }
        }
    }

    /**
     * Signal handlers receive the signal number. PHPStan types the value returned by
     * pcntl_signal_get_handler() as a parameterless callable, so the assertion restores
     * the real signature before the previous handler is invoked.
     *
     * @phpstan-assert-if-true callable(int): mixed $handler
     */
    private static function isSignalHandler(mixed $handler): bool
    {
        return is_callable($handler);
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
