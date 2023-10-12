<?php

declare(strict_types=1);

/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
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
use Interop\Amqp\AmqpDestination;
use Interop\Amqp\AmqpMessage;
use Interop\Amqp\AmqpQueue;
use Interop\Amqp\AmqpTopic;
use Interop\Amqp\Impl\AmqpBind;
use Interop\Queue\Context;
use yii\base\Application as BaseApp;
use yii\base\Event;
use yii\base\NotSupportedException;
use yii\queue\cli\Queue as CliQueue;

/**
 * Amqp Queue.
 *
 * @property-read AmqpContext $context
 * @property-read AmqpQueue $queue
 *
 * @author Maksym Kotliar <kotlyar.maksim@gmail.com>
 * @since 2.0.2
 */
class Queue extends CliQueue
{
    public const ATTEMPT = 'yii-attempt';
    public const TTR = 'yii-ttr';
    public const DELAY = 'yii-delay';
    public const PRIORITY = 'yii-priority';
    public const ENQUEUE_AMQP_LIB = 'enqueue/amqp-lib';
    public const ENQUEUE_AMQP_EXT = 'enqueue/amqp-ext';
    public const ENQUEUE_AMQP_BUNNY = 'enqueue/amqp-bunny';

    /**
     * The connection to the broker could be configured as an array of options
     * or as a DSN string like amqp:, amqps:, amqps://user:pass@localhost:1000/vhost.
     *
     * @var string|null
     */
    public ?string $dsn = null;
    /**
     * The message queue broker's host.
     *
     * @var string|null
     */
    public ?string $host = null;
    /**
     * The message queue broker's port.
     *
     * @var string|null
     */
    public ?string $port = null;
    /**
     * This is RabbitMQ user which is used to login on the broker.
     *
     * @var string|null
     */
    public ?string $user = null;
    /**
     * This is RabbitMQ password which is used to login on the broker.
     *
     * @var string|null
     */
    public ?string $password = null;
    /**
     * Virtual hosts provide logical grouping and separation of resources.
     *
     * @var string|null
     */
    public ?string $vhost = null;
    /**
     * The time PHP socket waits for an information while reading. In seconds.
     *
     * @var float|null
     */
    public ?float $readTimeout = null;
    /**
     * The time PHP socket waits for an information while witting. In seconds.
     *
     * @var float|null
     */
    public ?float $writeTimeout = null;
    /**
     * The time RabbitMQ keeps the connection on idle. In seconds.
     *
     * @var float|null
     */
    public ?float $connectionTimeout = null;
    /**
     * The periods of time PHP pings the broker in order to prolong the connection timeout. In seconds.
     *
     * @var float|null
     */
    public ?float $heartbeat = 0;
    /**
     * PHP uses one shared connection if set true.
     *
     * @var bool|null
     */
    public ?bool $persisted = null;
    /**
     * Send keep-alive packets for a socket connection
     * @var bool
     * @since 2.3.6
     */
    public bool $keepalive = false;
    /**
     * The connection will be established as later as possible if set true.
     *
     * @var bool|null
     */
    public ?bool $lazy = null;
    /**
     * If false prefetch_count option applied separately to each new consumer on the channel
     * If true prefetch_count option shared across all consumers on the channel.
     *
     * @var bool|null
     */
    public ?bool $qosGlobal = null;
    /**
     * Defines number of message pre-fetched in advance on a channel basis.
     *
     * @var int|null
     */
    public ?int $qosPrefetchSize = null;
    /**
     * Defines number of message pre-fetched in advance per consumer.
     *
     * @var int|null
     */
    public ?int $qosPrefetchCount = null;
    /**
     * Defines whether secure connection should be used or not.
     *
     * @var bool|null
     */
    public ?bool $sslOn = null;
    /**
     * Require verification of SSL certificate used.
     *
     * @var bool|null
     */
    public ?bool $sslVerify = null;
    /**
     * Location of Certificate Authority file on local filesystem which should be used with the verify_peer context option to authenticate the identity of the remote peer.
     *
     * @var string|null
     */
    public ?string $sslCacert = null;
    /**
     * Path to local certificate file on filesystem.
     *
     * @var string|null
     */
    public ?string $sslCert = null;
    /**
     * Path to local private key file on filesystem in case of separate files for certificate (local_cert) and private key.
     *
     * @var string|null
     */
    public ?string $sslKey = null;
    /**
     * The queue used to consume messages from.
     *
     * @var string
     */
    public string $queueName = 'interop_queue';
    /**
     * Setting optional arguments for the queue (key-value pairs)
     * ```php
     * [
     *    'x-expires' => 300000,
     *    'x-max-priority' => 10
     * ]
     * ```
     *
     * @var array
     * @since 2.3.3
     * @see https://www.rabbitmq.com/queues.html#optional-arguments
     */
    public array $queueOptionalArguments = [];
    /**
     * Set of flags for the queue
     * @var int
     * @since 2.3.5
     * @see AmqpDestination
     */
    public int $queueFlags = AmqpQueue::FLAG_DURABLE;
    /**
     * The exchange used to publish messages to.
     *
     * @var string
     */
    public string $exchangeName = 'exchange';
    /**
     * The exchange type. Can take values: direct, fanout, topic, headers
     * @var string
     * @since 2.3.3
     */
    public string $exchangeType = AmqpTopic::TYPE_DIRECT;
    /**
     * Set of flags for the exchange
     * @var int
     * @since 2.3.5
     * @see AmqpDestination
     */
    public int $exchangeFlags = AmqpTopic::FLAG_DURABLE;
    /**
     * Routing key for publishing messages. Default is NULL.
     *
     * @var string|null
     */
    public ?string $routingKey = null;
    /**
     * Defines the amqp interop transport being internally used. Currently supports lib, ext and bunny values.
     *
     * @var string
     */
    public string $driver = self::ENQUEUE_AMQP_LIB;
    /**
     * The property contains a command class which used in cli.
     *
     * @var string command class name
     */
    public string $commandClass = Command::class;
    /**
     * Headers to send along with the message
     * ```php
     * [
     *    'header-1' => 'header-value-1',
     *    'header-2' => 'header-value-2',
     * ]
     * ```
     *
     * @var array
     * @since 2.3.6
     */
    public array $setMessageHeaders = [];

    /**
     * Amqp interop context.
     *
     * @var AmqpContext|Context|null
     */
    protected AmqpContext|null|Context $context = null;
    /**
     * List of supported amqp interop drivers.
     *
     * @var string[]
     */
    protected array $supportedDrivers = [
        self::ENQUEUE_AMQP_LIB,
        self::ENQUEUE_AMQP_EXT,
        self::ENQUEUE_AMQP_BUNNY
    ];
    /**
     * The property tells whether the setupBroker method was called or not.
     * Having it we can do broker setup only once per process.
     *
     * @var bool
     */
    protected bool $setupBrokerDone = false;
    /**
     * Created AmqpQueue instance
     *
     * @var AmqpQueue
     */
    protected AmqpQueue $queue;

    /**
     * @inheritdoc
     */
    public function init(): void
    {
        parent::init();
        Event::on(BaseApp::class, BaseApp::EVENT_AFTER_REQUEST, function () {
            $this->close();
        });

        if (extension_loaded('pcntl') && function_exists('pcntl_signal') && PHP_MAJOR_VERSION >= 7) {
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
    public function listen(): void
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
     * @return AmqpContext|null
     */
    public function getContext(): ?AmqpContext
    {
        $this->open();

        return $this->context;
    }

    /**
     * @inheritdoc
     */
    protected function pushMessage(string $payload, int $ttr, int $delay, mixed $priority): int|string|null
    {
        $this->open();
        $this->setupBroker();

        $topic = $this->context->createTopic($this->exchangeName);

        /** @var AmqpMessage $message */
        $message = $this->context->createMessage($payload);
        $message->setDeliveryMode(AmqpMessage::DELIVERY_MODE_PERSISTENT);
        $message->setMessageId(uniqid('', true));
        $message->setTimestamp(time());
        $message->setProperties(array_merge(
            $this->setMessageHeaders,
            [
                self::ATTEMPT => 1,
                self::TTR => $ttr,
            ]
        ));

        $producer = $this->context->createProducer();

        if ($delay) {
            $message->setProperty(self::DELAY, $delay);
            $producer->setDeliveryDelay($delay * 1000);
        }

        if ($priority) {
            $message->setProperty(self::PRIORITY, $priority);
            $producer->setPriority($priority);
        }

        if (null !== $this->routingKey) {
            $message->setRoutingKey($this->routingKey);
        }

        $producer->send($topic, $message);

        return $message->getMessageId();
    }

    /**
     * @inheritdoc
     */
    public function status($id): int
    {
        throw new NotSupportedException('Status is not supported in the driver.');
    }

    /**
     * Opens connection and channel.
     */
    protected function open(): void
    {
        if ($this->context) {
            return;
        }

        $connectionClass = match ($this->driver) {
            self::ENQUEUE_AMQP_LIB => AmqpLibConnectionFactory::class,
            self::ENQUEUE_AMQP_EXT => AmqpExtConnectionFactory::class,
            self::ENQUEUE_AMQP_BUNNY => AmqpBunnyConnectionFactory::class,
            default => throw new \LogicException(
                sprintf(
                    'The given driver "%s" is not supported. Drivers supported are "%s"',
                    $this->driver,
                    implode('", "', $this->supportedDrivers)
                )
            ),
        };

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
            'keepalive' => $this->keepalive,
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

        $config = array_filter($config, static function ($value) {
            return null !== $value;
        });

        /** @var AmqpConnectionFactory $factory */
        $factory = new $connectionClass($config);

        $this->context = $factory->createContext();

        if ($this->context instanceof DelayStrategyAware) {
            $this->context->setDelayStrategy(new RabbitMqDlxDelayStrategy());
        }
    }

    protected function setupBroker(): void
    {
        if ($this->setupBrokerDone) {
            return;
        }

        $this->queue = $this->createQueue();
        $this->queue->setFlags($this->queueFlags);
        $this->queue->setArguments($this->queueOptionalArguments);
        $this->context->declareQueue($this->queue);

        /** @var AmqpTopic $topic */
        $topic = $this->context->createTopic($this->exchangeName);
        $topic->setType($this->exchangeType);
        $topic->setFlags($this->exchangeFlags);
        $this->context->declareTopic($topic);

        $this->context->bind(new AmqpBind($this->queue, $topic, $this->routingKey));

        $this->setupBrokerDone = true;
    }

    /**
     * Closes connection and channel.
     */
    protected function close(): void
    {
        if (!$this->context) {
            return;
        }

        $this->context->close();
        $this->context = null;
        $this->setupBrokerDone = false;
    }

    protected function redeliver(AmqpMessage $message): void
    {
        $attempt = $message->getProperty(self::ATTEMPT, 1);

        /** @var AmqpMessage $newMessage */
        $newMessage = $this->context->createMessage(
            $message->getBody(),
            $message->getProperties(),
            $message->getHeaders()
        );
        $newMessage->setDeliveryMode($message->getDeliveryMode());
        $newMessage->setProperty(self::ATTEMPT, ++$attempt);

        $this->context->createProducer()->send(
             $this->context->createQueue($this->queueName),
             $newMessage
         );
    }

    private function createQueue(): AmqpQueue
    {
        return $this->context->createQueue($this->queueName);
    }
}
