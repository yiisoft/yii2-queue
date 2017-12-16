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
     * Listens queue and runs each job.
     *
     * @param bool $loop whether to continue listening when queue is empty.
     * @param int $timeout number of seconds to wait for next message.
     */
    public function run($loop, $timeout = 0)
    {
        while ($this->loop->canContinue()) {
            if ($payload = $this->getPheanstalk()->reserveFromTube($this->tube, $timeout)) {
                $info = $this->getPheanstalk()->statsJob($payload);
                if ($this->handleMessage(
                    $payload->getId(),
                    $payload->getData(),
                    $info->ttr,
                    $info->reserves
                )) {
                    $this->getPheanstalk()->delete($payload);
                }
            } elseif (!$loop) {
                break;
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