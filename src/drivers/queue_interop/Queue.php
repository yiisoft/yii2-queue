<?php

namespace yii\queue\queue_interop;

use Interop\Amqp\AmqpContext;
use Interop\Queue\PsrConnectionFactory;
use Interop\Queue\PsrContext;
use Interop\Queue\PsrProducer;
use yii\base\NotSupportedException;
use yii\queue\cli\Queue as CliQueue;

class Queue extends CliQueue
{
    /**
     * {@inheritdoc}
     */
    public $commandClass = Command::class;

    /**
     * @var string
     */
    public $factoryClass = null;

    /**
     * @var array
     */
    public $factoryConfig = [];

    /**
     * @var string
     */
    public $queueName = 'queue';

    /**
     * @var PsrContext
     */
    private $context;

    /**
     * @var PsrProducer
     */
    private $producer;

    /**
     * Listens queue and runs new jobs.
     */
    public function listen()
    {
        $consumer = $this->getContext()->createConsumer(
            $this->getContext()->createQueue($this->queueName)
        );

        while (true) {
            if ($message = $consumer->receive()) {
                list($ttr, $body) = explode(';', $message->getBody(), 2);
                if ($this->handleMessage(null, $body, $ttr, 1)) {
                    $consumer->acknowledge($message);
                }
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function pushMessage($message, $ttr, $delay, $priority)
    {
        if ($delay) {
            throw new NotSupportedException('Delayed work is not supported in the driver.');
        }

        if ($priority !== null) {
            throw new NotSupportedException('Job priority is not supported in the driver.');
        }

        if (null === $this->producer) {
            $this->producer = $this->getContext()->createProducer();
        }

        $this->producer->send(
            $this->getContext()->createQueue($this->queueName),
            $this->getContext()->createMessage("$ttr;$message")
        );

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
     * @return PsrContext
     */
    private function getContext()
    {
        if (null === $this->context) {
            if (empty($this->factoryClass)) {
                throw new \LogicException('The "factoryClass" option is required');
            }

            if (false == class_exists($this->factoryClass)) {
                throw new \LogicException(sprintf('The "factoryClass" option "%s" is not a class', $this->factoryClass));
            }

            $rc = new \ReflectionClass($this->factoryClass);
            if (false == $rc->implementsInterface(PsrConnectionFactory::class)) {
                throw new \LogicException(sprintf('The "factoryClass" option must contain a class that implements "%s" but it is not', PsrConnectionFactory::class));
            }

            /** @var PsrConnectionFactory $factory */
            $factory = new $this->factoryClass(isset($this->factoryConfig['dsn']) ? $this->factoryConfig['dsn'] : $this->factoryConfig);

            $this->context = $factory->createContext();

            if ($this->context instanceof AmqpContext) {
                $this->context->declareQueue($this->context->createQueue($this->queueName));
            }
        }

        return $this->context;
    }
}
