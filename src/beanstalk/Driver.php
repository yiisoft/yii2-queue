<?php
/**
 * @link https://github.com/zhuravljov/yii2-queue
 * @copyright Copyright (c) 2017 Roman Zhuravlev
 * @license http://opensource.org/licenses/BSD-3-Clause
 */

namespace zhuravljov\yii\queue\beanstalk;

use Pheanstalk\Pheanstalk;
use Pheanstalk\PheanstalkInterface;
use yii\base\BootstrapInterface;
use yii\helpers\Inflector;
use zhuravljov\yii\queue\Driver as BaseDriver;
use zhuravljov\yii\queue\Signal;

/**
 * Beanstalk Driver
 *
 * @author Roman Zhuravlev <zhuravljov@gmail.com>
 */
class Driver extends BaseDriver implements BootstrapInterface
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
     * @inheritdoc
     */
    public function bootstrap($app)
    {
        if ($app instanceof \yii\console\Application) {
            $app->controllerMap[Inflector::camel2id($this->queue->id)] = [
                'class' => Command::class,
                'driver' => $this,
            ];
        }
    }

    /**
     * @inheritdoc
     */
    public function push($job)
    {
        $this->getPheanstalk()->putInTube(
            $this->tube,
            $this->serialize($job),
            PheanstalkInterface::DEFAULT_PRIORITY,
            0,
            $this->ttr
        );
    }

    /**
     * @inheritdoc
     */
    public function later($job, $timeout)
    {
        $this->getPheanstalk()->putInTube(
            $this->tube,
            $this->serialize($job),
            PheanstalkInterface::DEFAULT_PRIORITY,
            $timeout,
            $this->ttr
        );
    }

    /**
     * Runs all jobs from queue.
     */
    public function run()
    {
        while ($message = $this->getPheanstalk()->reserveFromTube($this->tube, 0)) {
            $job = $this->unserialize($message->getData());
            if ($this->getQueue()->run($job)) {
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