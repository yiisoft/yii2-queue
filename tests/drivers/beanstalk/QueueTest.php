<?php

/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

declare(strict_types=1);

namespace tests\drivers\beanstalk;

use Pheanstalk\Pheanstalk;
use Pheanstalk\Values\JobId;
use Pheanstalk\Values\TubeName;
use RuntimeException;
use tests\app\PriorityJob;
use tests\app\RetryJob;
use tests\drivers\CliTestCase;
use Throwable;
use Yii;
use yii\queue\beanstalk\Queue;

/**
 * Beanstalk Queue Test.
 *
 * @author Roman Zhuravlev <zhuravljov@gmail.com>
 */
final class QueueTest extends CliTestCase
{
    public function testRun(): void
    {
        $job = $this->createSimpleJob();
        $this->getQueue()->push($job);
        $this->runProcess(['php', 'yii', 'queue/run']);

        $this->assertSimpleJobDone($job);
    }

    public function testStatus(): void
    {
        $job = $this->createSimpleJob();
        $id = $this->getQueue()->push($job);
        $isWaiting = $this->getQueue()->isWaiting($id);
        $this->runProcess(['php', 'yii', 'queue/run']);
        $isDone = $this->getQueue()->isDone($id);

        $this->assertTrue($isWaiting);
        $this->assertTrue($isDone);
    }

    public function testPriority(): void
    {
        $this->getQueue()->priority(100)->push(new PriorityJob(['number' => 1]));
        $this->getQueue()->priority(300)->push(new PriorityJob(['number' => 5]));
        $this->getQueue()->priority(200)->push(new PriorityJob(['number' => 3]));
        $this->getQueue()->priority(200)->push(new PriorityJob(['number' => 4]));
        $this->getQueue()->priority(100)->push(new PriorityJob(['number' => 2]));
        $this->runProcess(['php', 'yii', 'queue/run']);

        $this->assertEquals('12345', file_get_contents(PriorityJob::getFileName()));
    }

    public function testListen(): void
    {
        $this->startProcess(['php', 'yii', 'queue/listen', '1']);
        $job = $this->createSimpleJob();
        $this->getQueue()->push($job);

        $this->assertSimpleJobDone($job);
    }

    public function testLater(): void
    {
        $this->startProcess(['php', 'yii', 'queue/listen', '1']);
        $job = $this->createSimpleJob();
        $this->getQueue()->delay(2)->push($job);

        $this->assertSimpleJobLaterDone($job, 2);
    }

    public function testRetry(): void
    {
        $this->startProcess(['php', 'yii', 'queue/listen', '1']);
        $job = new RetryJob(['uid' => uniqid('', true)]);
        $this->getQueue()->push($job);
        sleep(6);

        $this->assertFileExists($job->getFileName());
        $this->assertEquals('aa', file_get_contents($job->getFileName()));
    }

    public function testRetryInProcess(): void
    {
        $queue = $this->getQueue();
        $attempt = 0;
        $queue->messageHandler = static function () use (&$attempt) {
            $attempt++;
            return $attempt >= 2;
        };

        $id = $queue->ttr(60)->push($this->createSimpleJob());
        $queue->run(false);

        $this->assertEquals(1, $attempt, 'The failed job must not be retried immediately.');
        $this->assertJobDelayedForRetry($id, 60);

        $this->kickJob($id);
        $queue->run(false);

        $this->assertEquals(2, $attempt);
        $this->assertFalse($this->jobIsExists($id));
    }

    public function testReleaseOnException(): void
    {
        $queue = $this->getQueue();
        $attempts = 0;
        $queue->messageHandler = static function () use (&$attempts) {
            $attempts++;
            if ($attempts <= 2) {
                throw new RuntimeException('test error');
            }
            return true;
        };

        $id = $queue->ttr(60)->push($this->createSimpleJob());
        $queue->run(false);

        $this->assertEquals(1, $attempts, 'The failed job must not be retried immediately.');
        $this->assertJobDelayedForRetry($id, 60);

        $this->kickJob($id);
        $queue->run(false);

        $this->assertEquals(2, $attempts);
        $this->assertJobDelayedForRetry($id, 60);

        $this->kickJob($id);
        $queue->run(false);

        $this->assertEquals(3, $attempts);
        $this->assertFalse($this->jobIsExists($id));
    }

    public function testRetryKeepsPriority(): void
    {
        $queue = $this->getQueue();
        $queue->messageHandler = static fn () => false;

        $id = $queue->ttr(60)->priority(100)->push($this->createSimpleJob());
        $queue->run(false);

        $this->assertJobDelayedForRetry($id, 60);
        $this->assertNotNull($id);
        $connection = Pheanstalk::create($queue->host, $queue->port);
        $this->assertSame(100, $connection->statsJob(new JobId($id))->priority);
    }

    public function testRetryAfterTtrExpired(): void
    {
        $queue = $this->getQueue();
        $attempts = 0;
        $queue->messageHandler = static function () use (&$attempts) {
            $attempts++;
            if ($attempts === 1) {
                // The job runs longer than its TTR, so beanstalkd returns it to the ready queue itself.
                sleep(3);
                return false;
            }
            return true;
        };

        $id = $queue->ttr(1)->push($this->createSimpleJob());
        $queue->run(false);

        $this->assertEquals(2, $attempts);
        $this->assertFalse($this->jobIsExists($id));
    }

    public function testWorkerIgnoresDefaultTube(): void
    {
        $queue = $this->getQueue();
        $handled = 0;
        $queue->messageHandler = static function () use (&$handled) {
            $handled++;
            return true;
        };

        $pheanstalk = Pheanstalk::create($queue->host, $queue->port);
        $pheanstalk->useTube(TubeName::default());
        $foreignJob = $pheanstalk->put('foreign job');

        try {
            $queue->run(false);

            $this->assertSame(0, $handled, 'The worker must not reserve jobs from the "default" tube.');
            $this->assertSame('ready', $pheanstalk->statsJob($foreignJob)->state->value);
        } finally {
            $pheanstalk->delete($foreignJob);
        }
    }

    public function testWorkerWithDefaultTube(): void
    {
        $queue = new Queue([
            'host' => $this->getQueue()->host,
            'port' => $this->getQueue()->port,
            'tube' => 'default',
        ]);
        $handled = 0;
        $queue->messageHandler = static function () use (&$handled) {
            $handled++;
            return true;
        };

        $id = $queue->push($this->createSimpleJob());
        $queue->run(false);

        $this->assertSame(1, $handled);
        $this->assertFalse($this->jobIsExists($id));
    }

    public function testRemove(): void
    {
        $id = $this->getQueue()->push($this->createSimpleJob());
        $this->assertTrue($this->jobIsExists($id));
        $this->runProcess(['php', 'yii', 'queue/remove', $id]);

        $this->assertFalse($this->jobIsExists($id));

        $queue = $this->getQueue();
        $jobId = $queue->push($this->createSimpleJob());

        $this->assertTrue($queue->remove($jobId));
        $this->assertFalse($queue->remove('007'));
    }

    public function testConnect(): void
    {
        $this->startProcess(['php', 'yii', 'queue/listen', '1']);

        $job = $this->createSimpleJob();

        $queue = new Queue(['host' => getenv('BEANSTALK_HOST') ?: 'localhost']);
        $queue->receiveTimeout = 1;
        $queue->connectTimeout = 5;
        $queue->push($job);

        $this->assertSimpleJobDone($job);
    }

    public function testStatusTube(): void
    {
        $queue = $this->getQueue();
        $queue->push($this->createSimpleJob());

        $statusTube = $queue->getStatsTube();

        $this->assertEquals('queue', $statusTube->name->value);
    }

    protected function getQueue(): Queue
    {
        return Yii::$app->beanstalkQueue;
    }

    protected function tearDown(): void
    {
        $this->getQueue()->messageHandler = null;
        $pheanstalk = Pheanstalk::create($this->getQueue()->host, $this->getQueue()->port);
        $tube = new TubeName($this->getQueue()->tube);
        $pheanstalk->watch($tube);
        while ($job = $pheanstalk->reserveWithTimeout(0)) {
            $pheanstalk->delete($job);
        }
        $pheanstalk->useTube($tube);
        while ($job = $pheanstalk->peekDelayed()) {
            $pheanstalk->delete($job);
        }
        parent::tearDown();
    }

    /**
     * Asserts that a failed job was put back to the tube with a delay instead of being retried at once.
     *
     * @param int|string|null $id of a job
     * @param int $ttr of the job in seconds
     */
    private function assertJobDelayedForRetry(int|string|null $id, int $ttr): void
    {
        $this->assertNotNull($id);
        $connection = Pheanstalk::create($this->getQueue()->host, $this->getQueue()->port);
        $stats = $connection->statsJob(new JobId($id));

        $this->assertSame('delayed', $stats->state->value);
        // The delay is the TTR minus the time the job took. The tests use a long TTR, so it stays
        // close to the TTR even on a slow machine.
        $this->assertGreaterThanOrEqual($ttr - 10, $stats->delay);
        $this->assertLessThanOrEqual($ttr, $stats->delay);
    }

    /**
     * Moves a delayed job to the ready state, so a test does not have to wait for its delay.
     *
     * @param int|string|null $id of a job
     */
    private function kickJob(int|string|null $id): void
    {
        $this->assertNotNull($id);
        $connection = Pheanstalk::create($this->getQueue()->host, $this->getQueue()->port);
        $connection->kickJob(new JobId($id));
    }

    /**
     * @param int|string|null $id of a job
     * @return bool
     */
    protected function jobIsExists(int|string|null $id): bool
    {
        $connection = Pheanstalk::create($this->getQueue()->host, $this->getQueue()->port);
        try {
            $connection->peek(new JobId($id));
            return true;
        } catch (Throwable) {
            return false;
        }
    }
}
