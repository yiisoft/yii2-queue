<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\queue\stomp;

use Enqueue\Stomp\StompConnectionFactory;
use Enqueue\Stomp\StompContext;
use Enqueue\Stomp\StompMessage;
use yii\base\Application as BaseApp;
use yii\base\Event;
use yii\base\NotSupportedException;
use yii\queue\cli\Queue as CliQueue;

class Queue extends CliQueue
{
    const ATTEMPT = 'yii-attempt';
    const TTR = 'yii-ttr';

    public $host;
    public $port;
    public $user;
    public $password;

    public $vhost;
    public $bufferSize;
    public $connectionTimeout;
    public $sync;
    public $lazy;
    public $sslOn;

    public $queueName = 'stomp_queue';

    public $commandClass = Command::class;
    /**
     * @var StompContext
     */
    protected $context;

    public function init()
    {
        parent::init();
        Event::on(BaseApp::class, BaseApp::EVENT_AFTER_REQUEST, function () {
            $this->close();
        });
    }


    protected function open()
    {
        if ($this->context) {
            return;
        }

        $config = [
            'host' => $this->host,
            'port' => $this->port,
            'login' => $this->user,
            'password' => $this->password,
            'vhost' => $this->vhost,
            'buffer_size' => $this->bufferSize,
            'connection_timeout' => $this->connectionTimeout,
            'sync' => $this->sync,
            'lazy' => $this->lazy,
            'ssl_on' => $this->sslOn,
        ];

        $config = array_filter($config, function ($value) {
            return null !== $value;
        });

        $factory = new StompConnectionFactory($config);

        $this->context = $factory->createContext();
    }

    public function run($repeat, $timeout = 0)
    {
        return $this->runWorker(function (callable $canContinue) use ($repeat, $timeout) {
            $this->open();
            $queue = $this->context->createQueue($this->queueName);
            $consumer = $this->context->createConsumer($queue);

            while ($canContinue()) {
                if ($message = $consumer->receive()) {
                    if ($message->isRedelivered()) {
                        $consumer->acknowledge($message);

                        $this->redeliver($message);

                        continue;
                    }

                    $ttr = $message->getProperty(self::TTR);
                    $attempt = $message->getProperty(self::ATTEMPT, 1);

                    if ($this->handleMessage($message->getMessageId(), $message->getBody(), $ttr, $attempt)) {
                        $consumer->acknowledge($message);
                    } else {
                        $consumer->acknowledge($message);

                        $this->redeliver($message);
                    }
                } elseif (!$repeat) {
                    break;
                } elseif ($timeout) {
                    sleep($timeout);
                }
            }
        });
    }

    /**
     * @inheritdoc
     */
    protected function pushMessage($message, $ttr, $delay, $priority)
    {
        $this->open();

        $queue = $this->context->createQueue($this->queueName);
        $message = $this->context->createMessage($message);
        $message->setMessageId(uniqid('', true));
        $message->setPersistent(true);
        $message->setProperty(self::ATTEMPT, 1);
        $message->setProperty(self::TTR, $ttr);

        $producer = $this->context->createProducer();

        if ($delay) {
            throw new NotSupportedException('Delayed work is not supported in the driver.');
        }

        if ($priority) {
            throw new NotSupportedException('Job priority is not supported in the driver.');
        }

        $producer->send($queue, $message);

        return $message->getMessageId();
    }

    protected function close()
    {
        if (!$this->context) {
            return;
        }

        $this->context->close();
        $this->context = null;
    }

    /**
     * @inheritdoc
     */
    public function status($id)
    {
        throw new NotSupportedException('Status is not supported in the driver.');
    }

    protected function redeliver(StompMessage $message)
    {
        $attempt = $message->getProperty(self::ATTEMPT, 1);

        $newMessage = $this->context->createMessage($message->getBody(), $message->getProperties(), $message->getHeaders());
        $newMessage->setProperty(self::ATTEMPT, ++$attempt);

        $this->context->createProducer()->send(
            $this->context->createQueue($this->queueName),
            $newMessage
        );
    }
}
