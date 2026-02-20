<?php

/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

declare(strict_types=1);

namespace yii\queue\beanstalk;

use Throwable;
use yii\helpers\BaseConsole;
use yii\helpers\Console;
use yii\queue\cli\Action;
use yii\queue\cli\Queue as CliQueue;

/**
 * Info about queue status.
 *
 * @author Roman Zhuravlev <zhuravljov@gmail.com>
 */
class InfoAction extends Action
{
    /**
     * @var Queue
     */
    public CliQueue $queue;

    /**
     * Info about queue status.
     */
    public function run(): void
    {
        Console::output(
            $this->format('Statistical information about the tube:', BaseConsole::FG_GREEN)
        );

        try {
            foreach ($this->queue->getStatsTube() as $key => $value) {
                Console::stdout($this->format("- $key: ", BaseConsole::FG_YELLOW));
                Console::output((string)$value);
            }
        } catch (Throwable) {
            Console::stdout(
                $this->format('Tube not found or empty', BaseConsole::FG_RED)
            );
        }
    }
}
