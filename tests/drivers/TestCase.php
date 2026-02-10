<?php

/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

declare(strict_types=1);

namespace tests\drivers;

use tests\app\SimpleJob;
use Yii;
use yii\queue\Queue;

/**
 * Driver Test Case.
 *
 * @author Roman Zhuravlev <zhuravljov@gmail.com>
 */
abstract class TestCase extends \tests\TestCase
{
    abstract protected function getQueue(): Queue;

    protected function createSimpleJob(): SimpleJob
    {
        return new SimpleJob([
            'uid' => uniqid('', true),
        ]);
    }

    protected function assertSimpleJobDone(SimpleJob $job): void
    {
        $timeout = 5000000; // 5 sec
        $step = 50000;
        while (!file_exists($job->getFileName()) && $timeout > 0) {
            usleep($step);
            $timeout -= $step;
        }
        $this->assertFileExists($job->getFileName());
    }

    protected function assertSimpleJobLaterDone(SimpleJob $job, int $delay): void
    {
        $time = time() + $delay;
        sleep($delay);
        $timeout = 5000000; // 5 sec
        $step = 50000;
        while (!file_exists($job->getFileName()) && $timeout > 0) {
            usleep($step);
            $timeout -= $step;
        }
        $this->assertFileExists($job->getFileName());
        $this->assertGreaterThanOrEqual($time, filemtime($job->getFileName()));
    }

    protected function tearDown(): void
    {
        // Removes temp job files
        foreach (glob(Yii::getAlias('@runtime/job-*.lock')) as $fileName) {
            unlink($fileName);
        }

        parent::tearDown();
    }
}
