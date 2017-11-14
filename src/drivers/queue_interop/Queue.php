<?php

namespace yii\queue\queue_interop;

use yii\base\NotSupportedException;
use yii\queue\cli\Queue as CliQueue;
use yii\queue\queue_interop\driver\AmqpDriver;

class Queue extends CliQueue
{
    /**
     * {@inheritdoc}
     */
    public $commandClass = Command::class;

    /**
     * @var array
     */
    public $config;

    /**
     * @var Driver
     */
    private $driver;

    /**
     * Listens queue and runs new jobs.
     */
    public function listen()
    {
        $consumer = $this->getDriver()->getConsumer();

        while (true) {
            if ($message = $consumer->receive()) {
                if ($message->isRedelivered()) {
                    $this->driver->redeliver($message);
                } else {
                    $ttr = $message->getProperty(Driver::H_TTR);
                    $attempt = $message->getProperty(Driver::H_ATTEMPT, 1);

                    $start = time();
                    if ($this->handleMessage($message->getMessageId(), $message->getBody(), $ttr, $attempt)) {
                        $end = time();

                        if ($ttr && ($end - $start) >= $ttr) {
                            $consumer->reject($message, true);
                        } else {
                            $consumer->acknowledge($message);
                        }
                    } else {
                        $consumer->reject($message, true);
                    }
                }
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function setupBroker()
    {
        $this->getDriver()->setupBroker();
    }

    /**
     * {@inheritdoc}
     */
    protected function pushMessage($message, $ttr, $delay, $priority)
    {
        $this->getDriver()->push($message, $ttr, $delay, $priority);

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function status($id)
    {
        throw new NotSupportedException('Status is not supported in the driver.');
    }

    /**
     * @return Driver
     */
    private function getDriver()
    {
        if (null === $this->driver) {
            if (false == isset($this->config['driver'])) {
                throw new \LogicException('The "driver" option is required');
            }

            switch ($this->config['driver']) {
                case AmqpDriver::class:
                    $this->driver = new AmqpDriver($this->config);
                    break;
                default:
                    throw new \LogicException('Unknown driver: "%s"', $this->config['driver']);
            }
        }

        return $this->driver;
    }
}
