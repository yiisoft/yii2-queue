<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\queue\amqp_interop;

use Enqueue\AmqpBunny\AmqpConnectionFactory as AmqpBunnyConnectionFactory;
use Enqueue\AmqpExt\AmqpConnectionFactory as AmqpExtConnectionFactory;
use Enqueue\AmqpLib\AmqpConnectionFactory as AmqpLibConnectionFactory;
use Enqueue\AmqpTools\DelayStrategyAware;
use Enqueue\AmqpTools\RabbitMqDlxDelayStrategy;
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
 * Amqp Queue.
 *
 * @author Maksym Kotliar <kotlyar.maksim@gmail.com>
 * @since 2.0.2
 */
class Queue extends CliQueue
{
    const ATTEMPT = 'yii-attempt';
    const TTR = 'yii-ttr';
    const DELAY = 'yii-delay';
    const PRIORITY = 'yii-priority';
    const ENQUEUE_AMQP_LIB = 'enqueue/amqp-lib';
    const ENQUEUE_AMQP_EXT = 'enqueue/amqp-ext';
    const ENQUEUE_AMQP_BUNNY = 'enqueue/amqp-bunny';

    /**
     * The connection to the borker could be configured as an array of options
     * or as a DSN string like amqp:, amqps:, amqps://user:pass@localhost:1000/vhost.
     *
     * @var string
     */
    public $dsn;
    /**
     * The message queue broker's host.
     *
     * @var string|null
     */
    public $host;
    /**
     * The message queue broker's port.
     *
     * @var string|null
     */
    public $port;
    /**
     * This is RabbitMQ user which is used to login on the broker.
     *
     * @var string|null
     */
    public $user;
    /**
     * This is RabbitMQ password which is used to login on the broker.
     *
     * @var string|null
     */
    public $password;
    /**
     * Virtual hosts provide logical grouping and separation of resources.
     *
     * @var string|null
     */
    public $vhost;
    /**
     * The time PHP socket waits for an information while reading. In seconds.
     *
     * @var float|null
     */
    public $readTimeout;
    /**
     * The time PHP socket waits for an information while witting. In seconds.
     *
     * @var float|null
     */
    public $writeTimeout;
    /**
     * The time RabbitMQ keeps the connection on idle. In seconds.
     *
     * @var float|null
     */
    public $connectionTimeout;
    /**
     * The periods of time PHP pings the broker in order to prolong the connection timeout. In seconds.
     *
     * @var float|null
     */
    public $heartbeat;
    /**
     * PHP uses one shared connection if set true.
     *
     * @var bool|null
     */
    public $persisted;
    /**
     * The connection will be established as later as possible if set true.
     *
     * @var bool|null
     */
    public $lazy;
    /**
     * If false prefetch_count option applied separately to each new consumer on the channel
     * If true prefetch_count option shared across all consumers on the channel.
     *
     * @var bool|null
     */
    public $qosGlobal;
    /**
     * Defines number of message pre-fetched in advance on a channel basis.
     *
     * @var int|null
     */
    public $qosPrefetchSize;
    /**
     * Defines number of message pre-fetched in advance per consumer.
     *
     * @var int|null
     */
    public $qosPrefetchCount;
    /**
     * Defines whether secure connection should be used or not.
     *
     * @var bool|null
     */
    public $sslOn;
    /**
     * Require verification of SSL certificate used.
     *
     * @var bool|null
     */
    public $sslVerify;
    /**
     * Location of Certificate Authority file on local filesystem which should be used with the verify_peer context option to authenticate the identity of the remote peer.
     *
     * @var string|null
     */
    public $sslCacert;
    /**
     * Path to local certificate file on filesystem.
     *
     * @var string|null
     */
    public $sslCert;
    /**
     * Path to local private key file on filesystem in case of separate files for certificate (local_cert) and private key.
     *
     * @var string|null
     */
    public $sslKey;
    /**
     * The queue used to consume messages from.
     *
     * @var string
     */
    public $queueName = 'interop_queue';
    /**
     * The exchange used to publish messages to.
     *
     * @var string
     */
    public $exchangeName = 'exchange';
    /**
     * Defines the amqp interop transport being internally used. Currently supports lib, ext and bunny values.
     *
     * @var string
     */
    public $driver = self::ENQUEUE_AMQP_LIB;
    /**
     * This property should be an integer indicating the maximum priority the queue should support. Default is 10.
     *
     * @var int
     */
    public $maxPriority = 10;
    /**
     * The property contains a command class which used in cli.
     *
     * @var string command class name
     */
    public $commandClass = Command::class;

    /**
     * Amqp interop context.
     *
     * @var AmqpContext
     */
    protected $context;
    /**
     * List of supported amqp interop drivers.
     *
     * @var string[]
     */
    protected $supportedDrivers = [self::ENQUEUE_AMQP_LIB, self::ENQUEUE_AMQP_EXT, self::ENQUEUE_AMQP_BUNNY];
    /**
     * The property tells whether the setupBroker method was called or not.
     * Having it we can do broker setup only once per process.
     *
     * @var bool
     */
    protected $setupBrokerDone = false;


    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
        Event::on(BaseApp::class, BaseApp::EVENT_AFTER_REQUEST, function () {
            $this->close();
        });

        if (extension_loaded('pcntl') && PHP_MAJOR_VERSION >= 7) {
            // https://github.com/php-amqplib/php-amqplib#unix-signals
            $signals = [SIGTERM, SIGQUIT, SIGINT, SIGHUP];
            
            foreach ($signals as $signal) {
                $oldHandler = null;
                // This got added in php 7.1 and might not exist on all supported versions
                if (function_exists('pcntl_signal_get_handler')) {
                    $oldHandler = pcntl_signal_get_handler($signal);
                }

                pcntl_signal($signal, static function ($signal) use ($oldHandler) {
                    if ($oldHandler && is_callable($oldHandler)) {
                        $oldHandler($signal);
                    }

                    pcntl_signal($signal, SIG_DFL);
                    posix_kill(posix_getpid(), $signal);
                });
            }
        }
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

        $callback = function (AmqpMessage $message, AmqpConsumer $consumer) {
            if ($message->isRedelivered()) {
                $consumer->acknowledge($message);

                $this->redeliver($message);

                return true;
            }

            $ttr = $message->getProperty(self::TTR);
            $attempt = $message->getProperty(self::ATTEMPT, 1);

            if ($this->handleMessage($message->getMessageId(), $message->getBody(), $ttr, $attempt)) {
                $consumer->acknowledge($message);
            } else {
                $consumer->acknowledge($message);

                $this->redeliver($message);
            }

            return true;
        };

        $subscriptionConsumer = $this->context->createSubscriptionConsumer();
        $subscriptionConsumer->subscribe($consumer, $callback);
        $subscriptionConsumer->consume();
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
        $message->setProperty(self::ATTEMPT, 1);
        $message->setProperty(self::TTR, $ttr);

        $producer = $this->context->createProducer();

        if ($delay) {
            $message->setProperty(self::DELAY, $delay);
            $producer->setDeliveryDelay($delay * 1000);
        }

        if ($priority) {
            $message->setProperty(self::PRIORITY, $priority);
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
     * Opens connection and channel.
     */
    protected function open()
    {
        if ($this->context) {
            return;
        }

        switch ($this->driver) {
            case self::ENQUEUE_AMQP_LIB:
                $connectionClass = AmqpLibConnectionFactory::class;
                break;
            case self::ENQUEUE_AMQP_EXT:
                $connectionClass = AmqpExtConnectionFactory::class;
                break;
            case self::ENQUEUE_AMQP_BUNNY:
                $connectionClass = AmqpBunnyConnectionFactory::class;
                break;
            default:
                throw new \LogicException(sprintf('The given driver "%s" is not supported. Drivers supported are "%s"', $this->driver, implode('", "', $this->supportedDrivers)));
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

        $config = array_filter($config, function ($value) {
            return null !== $value;
        });

        /** @var AmqpConnectionFactory $factory */
        $factory = new $connectionClass($config);

        $this->context = $factory->createContext();

        if ($this->context instanceof DelayStrategyAware) {
            $this->context->setDelayStrategy(new RabbitMqDlxDelayStrategy());
        }
    }

    protected function setupBroker()
    {
        if ($this->setupBrokerDone) {
            return;
        }

        $queue = $this->context->createQueue($this->queueName);
        $queue->addFlag(AmqpQueue::FLAG_DURABLE);
        $queue->setArguments(['x-max-priority' => $this->maxPriority]);
        $this->context->declareQueue($queue);

        $topic = $this->context->createTopic($this->exchangeName);
        $topic->setType(AmqpTopic::TYPE_DIRECT);
        $topic->addFlag(AmqpTopic::FLAG_DURABLE);
        $this->context->declareTopic($topic);

        $this->context->bind(new AmqpBind($queue, $topic));

        $this->setupBrokerDone = true;
    }

    /**
     * Closes connection and channel.
     */
    protected function close()
    {
        if (!$this->context) {
            return;
        }

        $this->context->close();
        $this->context = null;
        $this->setupBrokerDone = false;
    }

    /**
     * {@inheritdoc}
     */
    protected function redeliver(AmqpMessage $message)
    {
        $attempt = $message->getProperty(self::ATTEMPT, 1);

        $newMessage = $this->context->createMessage($message->getBody(), $message->getProperties(), $message->getHeaders());
        $newMessage->setDeliveryMode($message->getDeliveryMode());
        $newMessage->setProperty(self::ATTEMPT, ++$attempt);

        $this->context->createProducer()->send(
             $this->context->createQueue($this->queueName),
             $newMessage
         );
    }
}
