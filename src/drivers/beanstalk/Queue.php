<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\queue\beanstalk;

use Pheanstalk\Exception\ServerException;
use Pheanstalk\Pheanstalk;
use Pheanstalk\PheanstalkInterface;
use yii\base\InvalidParamException;
use yii\queue\cli\Queue as CliQueue;
use yii\queue\cli\Signal;

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
     * @var string command class name
     */
    public $commandClass = Command::class;


    /**
     * Runs all jobs from queue.
     */
    public function run()
    {
        while ($payload = $this->getPheanstalk()->reserveFromTube($this->tube, 0)) {
            $info = $this->getPheanstalk()->statsJob($payload);
            if ($this->handleMessage(
                $payload->getId(),
                $payload->getData(),
                $info->ttr,
                $info->reserves
            )) {
                $this->getPheanstalk()->delete($payload);
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
                $info = $this->getPheanstalk()->statsJob($payload);
                if ($this->handleMessage(
                    $payload->getId(),
                    $payload->getData(),
                    $info->ttr,
                    $info->reserves
                )) {
                    $this->getPheanstalk()->delete($payload);
                }
            }
        }
    }

    /**
     * @inheritdoc
     */
    public function status($id)
    {
        if (!is_numeric($id) || $id <= 0) {
            throw new InvalidParamException("Unknown message ID: $id.");
        }

        try {
            $stats = $this->getPheanstalk()->statsJob($id);
            if ($stats['state'] === 'reserved') {
                return self::STATUS_RESERVED;
            } else {
                return self::STATUS_WAITING;
            }
        } catch (ServerException $e) {
            if ($e->getMessage() === 'Server reported NOT_FOUND') {
                return self::STATUS_DONE;
            } else {
                throw $e;
            }
        }
    }

    /**
     * Removes a job by ID
     *
     * @param int $id of a job
     * @return bool
     * @since 2.0.1
     */
    public function remove($id)
    {
        try {
            $job = $this->getPheanstalk()->peek($id);
            $this->getPheanstalk()->delete($job);
            return true;
        } catch (ServerException $e) {
            if (strpos($e->getMessage(), 'NOT_FOUND') === 0) {
                return false;
            } else {
                throw $e;
            }
        }
    }

    /**
     * @inheritdoc
     */
    protected function pushMessage($message, $ttr, $delay, $priority)
    {
        return $this->getPheanstalk()->putInTube(
            $this->tube,
            $message,
            $priority ?: PheanstalkInterface::DEFAULT_PRIORITY,
            $delay,
            $ttr
        );
    }

    /**
     * @return array tube statistics
     */
    public function getStatsTube()
    {
        return $this->getPheanstalk()->statsTube($this->tube);
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