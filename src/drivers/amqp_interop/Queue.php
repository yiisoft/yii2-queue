<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\queue\amqp_interop;

use Enqueue\AmqpTools\DelayStrategyAware;
use Enqueue\AmqpTools\RabbitMqDlxDelayStrategy;
use Enqueue\AmqpLib\AmqpConnectionFactory as AmqpLibConnectionFactory;
use Enqueue\AmqpExt\AmqpConnectionFactory as AmqpExtConnectionFactory;
use Enqueue\AmqpBunny\AmqpConnectionFactory as AmqpBunnyConnectionFactory;
use Interop\Amqp\AmqpConnectionFactory;
use Interop\Amqp\AmqpConsumer;
use Interop\Amqp\AmqpContext;
use Interop\Amqp\AmqpMessage;
use Interop\Amqp\AmqpQueue;
use Interop\Amqp\AmqpTopic;
use Interop\Amqp\Impl\AmqpBind;
use yii\base\Application as BaseApp;
use yii\base\Event;
use yii\base\NotSupportedException;
use yii\queue\cli\Queue as CliQueue;

/**
 * Amqp Queue
 *
 * @author Maksym Kotliar <kotlyar.maksim@gmail.com>
 */
class Queue extends CliQueue
{
    const H_ATTEMPT = 'yii-attempt';
    const H_TTR = 'yii-ttr';
    const H_DELAY = 'yii-delay';
    const H_PRIORITY = 'yii-priority';

    /**
     * @var string like amqp:, amqps:, amqps://user:pass@localhost:1000/vhost
     */
    public $dsn = null;

    /**
     * @var string|null
     */
    public $host = null;

    /**
     * @var string|null
     */
    public $port = null;

    /**
     * @var string|null
     */
    public $user = null;

    /**
     * @var string|null
     */
    public $password = null;

    /**
     * @var string|null
     */
    public $vhost = null;

    /**
     * In seconds
     *
     * @var float|null
     */
    public $readTimeout = null;

    /**
     * In seconds
     *
     * @var float|null
     */
    public $writeTimeout = null;

    /**
     * In seconds
     *
     * @var float|null
     */
    public $connectionTimeout = null;

    /**
     * In seconds
     *
     * @var float|null
     */
    public $heartbeat = null;

    /**
     * @var bool|null
     */
    public $persisted = null;

    /**
     * @var bool|null
     */
    public $lazy = null;

    /**
     * @var bool|null
     */
    public $qosGlobal = null;

    /**
     * @var int|null
     */
    public $qosPrefetchSize = null;

    /**
     * @var int|null
     */
    public $qosPrefetchCount = null;

    /**
     * @var bool|null
     */
    public $sslOn = null;

    /**
     * @var bool|null
     */
    public $sslVerify = null;

    /**
     * @var string|null
     */
    public $sslCacert = null;

    /**
     * @var string|null
     */
    public $sslCert = null;

    /**
     * @var string|null
     */
    public $sslKey = null;

    /**
     * @var string
     */
    public $queueName = 'interop_queue';

    /**
     * @var string
     */
    public $exchangeName = 'exchange';

    /**
     * @var string
     */
    public $driver = 'lib';

    /**
     * @var string command class name
     */
    public $commandClass = Command::class;

    /**
     * @var AmqpContext
     */
    protected $context;

    /**
     * @var string[]
     */
    protected $supportedDrivers = ['lib', 'ext', 'bunny'];

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
        Event::on(BaseApp::class, BaseApp::EVENT_AFTER_REQUEST, function () {
            $this->close();
        });
    }

    /**
     * Listens amqp-queue and runs new jobs.
     */
    public function listen()
    {
        $this->open();
        $this->setupBroker();

        $queue = $this->context->createQueue($this->queueName);
        $consumer = $this->context->createConsumer($queue);
        $this->context->subscribe($consumer, function(AmqpMessage $message, AmqpConsumer $consumer) {
            if ($message->isRedelivered()) {
                $consumer->acknowledge($message);

                $this->redeliver($message);

                return true;
            }

            $ttr = $message->getProperty(self::H_TTR);
            $attempt = $message->getProperty(self::H_ATTEMPT, 1);

            if ($this->handleMessage($message->getMessageId(), $message->getBody(), $ttr, $attempt)) {
                $consumer->acknowledge($message);
            } else {
                $consumer->acknowledge($message);

                $this->redeliver($message);
            }

            return true;
        });

        $this->context->consume();
    }

    /**
     * @return AmqpContext
     */
    public function getContext()
    {
        $this->open();

        return $this->context;
    }

    /**
     * @inheritdoc
     */
    protected function pushMessage($payload, $ttr, $delay, $priority)
    {
        $this->open();
        $this->setupBroker();

        $topic = $this->context->createTopic($this->exchangeName);

        $message = $this->context->createMessage($payload);
        $message->setDeliveryMode(AmqpMessage::DELIVERY_MODE_PERSISTENT);
        $message->setMessageId(uniqid('', true));
        $message->setTimestamp(time());
        $message->setProperty(self::H_ATTEMPT, 1);
        $message->setProperty(self::H_TTR, $ttr);

        $producer = $this->context->createProducer();

        if ($delay) {
            $message->setProperty(self::H_DELAY, $delay);
            $producer->setDeliveryDelay($delay * 1000);
        }

        if ($priority) {
            $message->setProperty(self::H_PRIORITY, $priority);
            $producer->setPriority($priority);
        }

        $producer->send($topic, $message);

        return $message->getMessageId();
    }

    /**
     * @inheritdoc
     */
    public function status($id)
    {
        throw new NotSupportedException('Status is not supported in the driver.');
    }

    /**
     * Opens connection and channel
     */
    protected function open()
    {
        if ($this->context) {
            return;
        }

        switch ($this->driver) {
            case 'lib':
                $connectionClass = AmqpLibConnectionFactory::class;

                break;
            case 'ext':
                $connectionClass = AmqpExtConnectionFactory::class;

                break;
            case 'bunny':
                $connectionClass = AmqpBunnyConnectionFactory::class;

                break;

            default:
                throw new \LogicException(sprintf('The given driver "%s" is not supported. Supported are "%s"', $this->driver, implode('", "', $this->supportedDrivers)));
        }

        $config = [
            'dsn' => $this->dsn,
            'host' => $this->host,
            'port' => $this->port,
            'user' => $this->user,
            'pass' => $this->password,
            'vhost' => $this->vhost,
            'read_timeout' => $this->readTimeout,
            'write_timeout' => $this->writeTimeout,
            'connection_timeout' => $this->connectionTimeout,
            'heartbeat' => $this->heartbeat,
            'persisted' => $this->persisted,
            'lazy' => $this->lazy,
            'qos_global' => $this->qosGlobal,
            'qos_prefetch_size' => $this->qosPrefetchSize,
            'qos_prefetch_count' => $this->qosPrefetchCount,
            'ssl_on' => $this->sslOn,
            'ssl_verify' => $this->sslVerify,
            'ssl_cacert' => $this->sslCacert,
            'ssl_cert' => $this->sslCert,
            'ssl_key' => $this->sslKey,
        ];

        $config = array_filter($config, function($value) {
            return null !== $value;
        });

        /** @var AmqpConnectionFactory $factory */
        $factory = new $connectionClass($config);

        $this->context = $factory->createContext();

        if ($this->context instanceof DelayStrategyAware) {
            $this->context->setDelayStrategy(new RabbitMqDlxDelayStrategy());
        }
    }

    public function setupBroker()
    {
        $queue = $this->context->createQueue($this->queueName);
        $queue->addFlag(AmqpQueue::FLAG_DURABLE);
        $queue->setArguments(['x-max-priority' => 4]);
        $this->context->declareQueue($queue);

        $topic = $this->context->createTopic($this->exchangeName);
        $topic->setType(AmqpTopic::TYPE_DIRECT);
        $topic->addFlag(AmqpTopic::FLAG_DURABLE);
        $this->context->declareTopic($topic);

        $this->context->bind(new AmqpBind($queue, $topic));
    }

    /**
     * Closes connection and channel
     */
    protected function close()
    {
        if (!$this->context) {
            return;
        }

        $this->context->close();
    }

    /**
     * {@inheritdoc}
     */
    protected function redeliver(AmqpMessage $message)
    {
         $attempt = $message->getProperty(self::H_ATTEMPT, 1);

         $newMessage = $this->context->createMessage($message->getBody(), $message->getProperties(), $message->getHeaders());
         $newMessage->setDeliveryMode($message->getDeliveryMode());
         $newMessage->setProperty(self::H_ATTEMPT, ++$attempt);

         $this->context->createProducer()->send(
             $this->context->createQueue($this->queueName),
             $newMessage
         );
     }
}
