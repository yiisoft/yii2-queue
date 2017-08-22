<?php

namespace yii\queue\queue_interop\driver;

use Enqueue\AmqpTools\DelayStrategyAware;
use Enqueue\AmqpTools\RabbitMqDelayPluginDelayStrategy;
use Enqueue\AmqpTools\RabbitMqDlxDelayStrategy;
use Enqueue\AmqpLib\AmqpConnectionFactory;
use Interop\Amqp\AmqpConsumer;
use Interop\Amqp\AmqpContext;
use Interop\Amqp\AmqpMessage;
use Interop\Amqp\AmqpQueue;
use Interop\Queue\PsrMessage;
use yii\queue\queue_interop\Driver;

class AmqpDriver implements Driver
{
    const RABBITMQ_DELAY_DLX = 'dlx';
    const RABBITMQ_DELAY_DELAYED_MESSAGE_PLUGIN = 'delayed_message_plugin';

    /**
     * @var array
     */
    private $config;

    /**
     * @var AmqpContext
     */
    private $context;

    /**
     * @var AmqpConsumer
     */
    private $consumer;

    public function __construct($config)
    {
        $this->config = array_replace([
            'factory' => AmqpConnectionFactory::class,
            'delay_strategy' => self::RABBITMQ_DELAY_DLX,
            'queue_name' => 'yii-queue',
        ], $config);
    }

    public function setupBroker()
    {
        $this->getContext()->declareQueue($this->getQueue());
    }

    /**
     * @param string $message
     * @param int    $ttr
     * @param int    $delay
     * @param int    $priority
     */
    public function push($message, $ttr, $delay, $priority)
    {
        $message = $this->createMessage($message, $ttr, $delay, $priority);
        $producer = $this->getContext()->createProducer();

        if ($delay = $message->getProperty(self::H_DELAY)) {
            $producer->setDeliveryDelay($delay * 1000);
        }

        if ($priority = $message->getProperty(self::H_PRIORITY)) {
            $producer->setPriority($priority);
        }

        $producer->send($this->getQueue(), $message);
    }

    /**
     * {@inheritdoc}
     */
    public function redeliver(PsrMessage $message)
    {
        $attempt = $message->getProperty(self::H_ATTEMPT, 1);

        $newMessage = $this->getContext()->createMessage($message->getBody(), $message->getProperties(), $message->getHeaders());
        $newMessage->setProperty(self::H_ATTEMPT, ++$attempt);

        $this->getConsumer()->acknowledge($message);

        $this->getContext()->createProducer()->send($this->getQueue(), $newMessage);
    }

    public function getConsumer()
    {
        if (null === $this->consumer) {
            $this->consumer = $this->getContext()->createConsumer($this->getQueue());
        }

        return $this->consumer;
    }

    public function getContext()
    {
        if (null === $this->context) {
            switch ($this->config['factory']) {
                case \Enqueue\AmqpLib\AmqpConnectionFactory::class:
                    $factory = new \Enqueue\AmqpLib\AmqpConnectionFactory($this->config);
                    break;
                case \Enqueue\AmqpExt\AmqpConnectionFactory::class:
                    $factory = new \Enqueue\AmqpExt\AmqpConnectionFactory($this->config);
                    break;
                default:
                    throw new \LogicException(sprintf('Unknown amqp factory: "%s"', $this->config['factory']));
            }

            if ($factory instanceof DelayStrategyAware) {
                switch ($this->config['delay_strategy']) {
                    case self::RABBITMQ_DELAY_DLX:
                        $factory->setDelayStrategy(new RabbitMqDlxDelayStrategy());
                        break;
                    case self::RABBITMQ_DELAY_DELAYED_MESSAGE_PLUGIN:
                        $factory->setDelayStrategy(new RabbitMqDelayPluginDelayStrategy());
                        break;
                    default:
                        throw new \LogicException(sprintf('Unknown rabbitmq delay strategy: "%s"', $this->config['delay_strategy']));
                }
            }

            $this->context = $factory->createContext();
        }

        return $this->context;
    }

    /**
     * @param string $message
     * @param int    $ttr
     * @param int    $delay
     * @param int    $priority
     *
     * @return AmqpMessage
     */
    public function createMessage($message, $ttr, $delay, $priority)
    {
        $message = $this->getContext()->createMessage($message);
        $message->setMessageId(uniqid('', true));
        $message->setProperty(self::H_ATTEMPT, 1);
        $message->setProperty(self::H_TTR, $ttr);
        $message->setProperty(self::H_DELAY, $delay);
        $message->setProperty(self::H_PRIORITY, $priority);

        return $message;
    }

    /**
     * @return AmqpQueue
     */
    public function getQueue()
    {
        $queue = $this->getContext()->createQueue($this->config['queue_name']);
        $queue->addFlag(AmqpQueue::FLAG_DURABLE);
        $queue->setArguments(['x-max-priority' => 4]);

        return $queue;
    }
}
