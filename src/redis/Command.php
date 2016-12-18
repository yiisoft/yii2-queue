<?php

namespace zhuravljov\yii\queue\redis;

use yii\helpers\Console;
use zhuravljov\yii\queue\Command as BaseCommand;

/**
 * Manages application redis-queue.
 *
 * @author Roman Zhuravlev <zhuravljov@gmail.com>
 */
class Command extends BaseCommand
{
    /**
     * @var string
     */
    public $defaultAction = 'stats';
    /**
     * @var Driver
     */
    public $driver;

    /**
     * Runs all jobs from redis-queue.
     * It can be used as cron job.
     */
    public function actionRun()
    {
        $this->driver->run();
    }

    /**
     * Listens redis-queue and runs new jobs.
     * It can be used as demon process.
     */
    public function actionListen()
    {
        $this->driver->listen();
    }

    /**
     * Returns statistics
     */
    public function actionStats()
    {
        echo Console::ansiFormat('Jobs', [Console::FG_GREEN]);
        echo PHP_EOL;
        echo Console::ansiFormat('- reserved: ', [Console::FG_YELLOW]);
        echo $this->driver->getReservedCount();
        echo PHP_EOL;

        if ($workersInfo = $this->driver->getWorkersInfo()) {
            echo Console::ansiFormat('Workers ', [Console::FG_GREEN]);
            echo PHP_EOL;
            foreach ($workersInfo as $name => $info) {
                echo Console::ansiFormat("- $name: ", [Console::FG_YELLOW]);
                echo $info['addr'];
                echo PHP_EOL;
            }
        }
    }
}