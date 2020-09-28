<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace tests\app\benchmark\waiting;

use Symfony\Component\Process\Process;
use Yii;
use yii\console\Exception as ConsoleException;
use yii\helpers\Console;
use yii\queue\Queue;

/**
 * Benchmark of job wait time.
 *
 * @author Roman Zhuravlev <zhuravljov@gmail.com>
 */
class Action extends \yii\base\Action
{
    /**
     * @var array
     */
    public $modes = [
        // Worker will be run in fast mode
        'fast' => [
            'gearmanQueue'     => ['gearman-queue/listen'      ,'--isolate=0'],
            'beanstalkQueue'   => ['beanstalk-queue/listen'    ,'--isolate=0'],
            'redisQueue'       => ['redis-queue/listen'        ,'--isolate=0'],
            'amqpQueue'        => ['amqp-queue/listen'         ,'--isolate=0'],
            'amqpInteropQueue' => ['amqp-interop-queue/listen' ,'--isolate=0'],
            'mysqlQueue'       => ['mysql-queue/listen', '1'   ,'--isolate=0'],
            'fileQueue'        => ['file-queue/listen' , '1'   ,'--isolate=0'],
            'stompQueue'       => ['stomp-queue/listen'        ,'--isolate=0'],
        ],
        // Worker will be run in isolate mode
        'isolate' => [
            'gearmanQueue'     => ['gearman-queue/listen'      ,'--isolate=1'],
            'beanstalkQueue'   => ['beanstalk-queue/listen'    ,'--isolate=1'],
            'redisQueue'       => ['redis-queue/listen'        ,'--isolate=1'],
            'amqpQueue'        => ['amqp-queue/listen'         ,'--isolate=1'],
            'amqpInteropQueue' => ['amqp-interop-queue/listen' ,'--isolate=1'],
            'mysqlQueue'       => ['mysql-queue/listen', '1'   ,'--isolate=1'],
            'fileQueue'        => ['file-queue/listen' , '1'   ,'--isolate=1'],
            'stompQueue'       => ['stomp-queue/listen', '1'   ,'--isolate=1'],
        ],
    ];
    /**
     * @var Process[]
     */
    private $workers = [];

    /**
     * Runs benchmark of job wait time.
     *
     * @param string $mode one of 'fast' or 'isolate'
     * @param int $jobCount number of jobs that will be pushed to a queue
     * @param int $workerCount number of workers that listen a queue
     * @param int $payloadSize additional job size
     * @throws
     */
    public function run($mode = 'fast', $jobCount = 1000, $workerCount = 10, $payloadSize = 0)
    {
        if (!isset($this->modes[$mode])) {
            throw new ConsoleException("Unknown mode: $mode.");
        }
        if ($jobCount <= 0) {
            throw new ConsoleException("Job count must be greater than zero.");
        }
        if ($workerCount <= 0) {
            throw new ConsoleException("Worker count must be greater than zero.");
        }

        foreach ($this->modes[$mode] as $queueName => $workerCommand) {
            /** @var Queue $queue */
            $queue = Yii::$app->get($queueName);

            // Starts worker
            $stdoutFileName = Yii::getAlias("@runtime/$queueName-out.log");
            file_put_contents($stdoutFileName, '');
            $this->startWorkers($workerCommand, $workerCount, function ($type, $buffer) use ($stdoutFileName) {
                file_put_contents($stdoutFileName, $buffer, FILE_APPEND | LOCK_EX);
            });

            // Prepares result storage
            sleep(2);
            $resultFileName = Yii::getAlias("@runtime/$queueName-result.log");
            file_put_contents($resultFileName, '');

            try {
                Console::startProgress(0, $jobCount, str_pad("- $queueName: ", 22));

                $pushedCount = 0;
                while ($pushedCount < $jobCount) {

                    // Push batch of jobs
                    $jobs = [];
                    for ($i = 0; $i < $workerCount && $pushedCount < $jobCount; $i++) {
                        $jobs[] = $job = new Job();
                        $job->resultFileName = $resultFileName;
                        $lockName = uniqid($queueName);
                        $job->lockFileName = Yii::getAlias("@runtime/$lockName.lock");
                        touch($job->lockFileName);
                        $job->pushedAt = microtime(true);
                        $job->payload = str_repeat('a', $payloadSize);
                        $queue->push($job);

                        Console::updateProgress(++$pushedCount, $jobCount);
                    }

                    // Waits end of execution of the jobs
                    do {
                        usleep(10000);
                        $handled = true;
                        foreach ($jobs as $job) {
                            /** @var Job $job */
                            if (file_exists($job->lockFileName)) {
                                $handled = false;
                                break;
                            }
                        }
                    } while (!$handled);
                }

                Console::endProgress(strtr(
                    'MEDIAN = {median} s; AVG = {avg} s; MIN = {min} s; MAX = {max} s' . PHP_EOL,
                    $this->calcResult($resultFileName, 4)
                ));
            } finally {
                $this->stopWorkers();
            }
        }
    }

    /**
     * Starts few workers.
     *
     * @param array $command
     * @param int $count
     * @param callable $callback
     */
    private function startWorkers($command, $count, callable $callback)
    {
        for ($i = 0; $i < $count; $i++) {
            $this->workers[] = $worker = new Process(array_merge(['php', 'tests/yii'], $command));
            $worker->start($callback);
        }
    }

    /**
     * Stops started workers.
     */
    private function stopWorkers()
    {
        foreach ($this->workers as $worker) {
            /** @var Process $worker */
            $worker->stop();
        }
        $this->workers = [];
    }

    /**
     * Calculates aggregated wait time.
     *
     * @param string $fileName of result storage
     * @param int $scale
     * @return array of aggregate results in seconds
     */
    private function calcResult($fileName, $scale = 4)
    {
        /** @var float[] $times */
        $times = explode("\n", trim(file_get_contents($fileName)));

        $count = count($times);
        sort($times, SORT_NUMERIC);
        $middleIndex = (int) floor($count / 2);
        $median = $times[$middleIndex];
        if ($count % 2 === 0) {
            $median = ($median + $times[$middleIndex - 1]) / 2;
        }
        $avg = array_sum($times) / $count;
        $min = min($times);
        $max = max($times);

        return [
            '{count}' => $count,
            '{median}' => number_format($median, $scale, '.', ''),
            '{avg}' => number_format($avg, $scale, '.', ''),
            '{min}' => number_format($min, $scale, '.', ''),
            '{max}' => number_format($max, $scale, '.', ''),
        ];
    }
}
