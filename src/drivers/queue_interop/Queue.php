<?php

namespace yii\queue\queue_interop;

use Enqueue\AmqpTools\DelayStrategyAware;
use Enqueue\AmqpTools\RabbitMqDelayPluginDelayStrategy;
use Enqueue\AmqpTools\RabbitMqDlxDelayStrategy;
use Interop\Amqp\AmqpContext;
use Interop\Amqp\AmqpQueue;
use Interop\Queue\PsrConnectionFactory;
use Interop\Queue\PsrContext;
use Interop\Queue\PsrProducer;
use yii\base\NotSupportedException;
use yii\queue\cli\Queue as CliQueue;

class Queue extends CliQueue
{
    const RABBITMQ_DELAY_DLX = 'dlx';
    const RABBITMQ_DELAY_DELAYED_MESSAGE_PLUGIN = 'delayed_message_plugin';

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
     * Supported strategies: "dlx", "delayed_message_plugin"
     *
     * @var string
     */
    public $rabbitmqDelayStrategy = self::RABBITMQ_DELAY_DLX;

    /**
     * @var string
     */
    public $queueName = 'queue';

    /**
     * @var PsrContext
     */
    private $context;

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
                } else {
                    $consumer->reject($message, true);
                }
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function pushMessage($message, $ttr, $delay, $priority)
    {
        $producer = $this->getContext()->createProducer();

        if ($delay !== null) {
            $producer->setDeliveryDelay($delay * 1000);
        }

        if  ($priority !== null) {
            $producer->setPriority($priority);
        }

        $producer->send(
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

            if (false == is_a($this->factoryClass, PsrConnectionFactory::class, true)) {
                throw new \LogicException(sprintf('The "factoryClass" option must contain a class that implements "%s" but it is not', PsrConnectionFactory::class));
            }

            /** @var PsrConnectionFactory $factory */
            $factory = new $this->factoryClass(isset($this->factoryConfig['dsn']) ? $this->factoryConfig['dsn'] : $this->factoryConfig);

            if ($factory instanceof DelayStrategyAware) {
                if (false != $this->rabbitmqDelayStrategy) {
                    switch ($this->rabbitmqDelayStrategy) {
                        case self::RABBITMQ_DELAY_DLX:
                            $factory->setDelayStrategy(new RabbitMqDlxDelayStrategy());
                            break;
                        case self::RABBITMQ_DELAY_DELAYED_MESSAGE_PLUGIN:
                            $factory->setDelayStrategy(new RabbitMqDelayPluginDelayStrategy());
                            break;
                        default:
                            throw new \LogicException(sprintf('Unknown rabbitmq delay strategy: "%s"', $this->rabbitmqDelayStrategy));
                    }
                }
            }

            $this->context = $factory->createContext();

            if ($this->context instanceof AmqpContext) {
                $queue = $this->context->createQueue($this->queueName);
                $queue->addFlag(AmqpQueue::FLAG_DURABLE);

                $this->context->declareQueue($queue);
            }
        }

        return $this->context;
    }
}
