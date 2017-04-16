<?php
/**
 * @link https://github.com/zhuravljov/yii2-queue
 * @copyright Copyright (c) 2017 Roman Zhuravlev
 * @license http://opensource.org/licenses/BSD-3-Clause
 */

namespace zhuravljov\yii\queue\drivers\beanstalk;

use Pheanstalk\Pheanstalk;
use Pheanstalk\PheanstalkInterface;
use zhuravljov\yii\queue\CliQueue;
use zhuravljov\yii\queue\Signal;

/**
 * Beanstalk Queue
 *
 * @author Roman Zhuravlev <zhuravljov@gmail.com>
 */
class Queue extends CliQueue
{
    /**
     * @var string connection host
     */
    public $host = 'localhost';
    /**
     * @var int connection port
     */
    public $port = PheanstalkInterface::DEFAULT_PORT;
    /**
     * @var string beanstalk tube
     */
    public $tube = 'queue';
    /**
     * @var int time to run: seconds a job can be reserved for.
     */
    public $ttr = PheanstalkInterface::DEFAULT_TTR;

    /**
     * @var string command class name
     */
    public $commandClass = Command::class;

    /**
     * Runs all jobs from queue.
     */
    public function run()
    {
        while ($message = $this->getPheanstalk()->reserveFromTube($this->tube, 0)) {
            if ($this->handleMessage($message->getData())) {
                $this->getPheanstalk()->delete($message);
            }
        }
    }

    /**
     * Listens queue and runs new jobs.
     *
     * @param integer $delay number of seconds for waiting new job.
     */
    public function listen($delay)
    {
        do {
            $this->run();
        } while (!Signal::isExit() && (!$delay || sleep($delay) === 0));
    }

    /**
     * @inheritdoc
     */
    protected function sendMessage($message, $timeout)
    {
        $this->getPheanstalk()->putInTube(
            $this->tube,
            $message,
            PheanstalkInterface::DEFAULT_PRIORITY,
            $timeout,
            $this->ttr
        );
    }

    /**
     * @return Pheanstalk
     */
    protected function getPheanstalk()
    {
        if (!$this->_pheanstalk) {
            $this->_pheanstalk = new Pheanstalk($this->host, $this->port);
        }
        return $this->_pheanstalk;
    }

    private $_pheanstalk;
}