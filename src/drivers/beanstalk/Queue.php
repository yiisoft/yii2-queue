<?php
/**
 * @link https://github.com/zhuravljov/yii2-queue
 * @copyright Copyright (c) 2017 Roman Zhuravlev
 * @license http://opensource.org/licenses/BSD-3-Clause
 */

namespace zhuravljov\yii\queue\beanstalk;

use Pheanstalk\Exception\ServerException;
use Pheanstalk\Pheanstalk;
use Pheanstalk\PheanstalkInterface;
use yii\base\InvalidParamException;
use zhuravljov\yii\queue\cli\Queue as CliQueue;
use zhuravljov\yii\queue\cli\Signal;

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
            if ($this->handleMessage($message->getId(), $message->getData())) {
                $this->getPheanstalk()->delete($message);
            }
        }
    }

    /**
     * Listens queue and runs new jobs.
     */
    public function listen()
    {
        while (!Signal::isExit()) {
            if ($payload = $this->getPheanstalk()->reserveFromTube($this->tube, 3)) {
                if ($this->handleMessage($payload->getId(), $payload->getData())) {
                    $this->getPheanstalk()->delete($payload);
                }
            }
        }
    }

    /**
     * @inheritdoc
     */
    protected function pushMessage($message, $timeout)
    {
        return $this->getPheanstalk()->putInTube(
            $this->tube,
            $message,
            PheanstalkInterface::DEFAULT_PRIORITY,
            $timeout,
            $this->ttr
        );
    }

    /**
     * @inheritdoc
     */
    protected function status($id)
    {
        if (!is_numeric($id) || $id <= 0) {
            throw new InvalidParamException("Unknown messages ID: $id.");
        }

        try {
            $stats = $this->getPheanstalk()->statsJob($id);
            if ($stats['state'] === 'reserved') {
                return self::STATUS_STARTED;
            } else {
                return self::STATUS_WAITING;
            }
        } catch (ServerException $e) {
            if ($e->getMessage() === 'Server reported NOT_FOUND') {
                return self::STATUS_FINISHED;
            } else {
                throw $e;
            }
        }
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