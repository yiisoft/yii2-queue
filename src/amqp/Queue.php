<?php
/**
 * @link https://github.com/zhuravljov/yii2-queue
 * @copyright Copyright (c) 2017 Roman Zhuravlev
 * @license http://opensource.org/licenses/BSD-3-Clause
 */

namespace zhuravljov\yii\queue\amqp;

use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use yii\base\Application as BaseApp;
use yii\base\BootstrapInterface;
use yii\base\Event;
use yii\base\NotSupportedException;
use yii\console\Application as ConsoleApp;
use zhuravljov\yii\queue\Queue as BaseQueue;

/**
 * Amqp Queue
 *
 * @author Roman Zhuravlev <zhuravljov@gmail.com>
 */
class Queue extends BaseQueue implements BootstrapInterface
{
    const EXCHANGE_DIRECT = 'direct';
    const EXCHANGE_TOPIC = 'topic';
    const EXCHANGE_FANOUT = 'fanout';

    public $host = 'localhost';
    public $port = 5672;
    public $user = 'guest';
    public $password = 'guest';
    public $queueName = 'queue';
    public $exchangeName = 'exchange';
    public $exchangeType = self::EXCHANGE_DIRECT;

    /**
     * @var AMQPStreamConnection
     */
    private $connection;
    /**
     * @var AMQPChannel
     */
    private $channel;

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
     * @inheritdoc
     */
    public function bootstrap($app)
    {
        if ($app instanceof ConsoleApp) {
            $app->controllerMap[$this->getId()] = [
                'class' => Command::class,
                'queue' => $this,
            ];
        }
    }

    /**
     * Listens amqp-queue and runs new jobs.
     */
    public function listen()
    {
        $this->open();
        $callback = function(AMQPMessage $message) {
            if ($this->handleMessage($message->body)) {
                $message->delivery_info['channel']->basic_ack($message->delivery_info['delivery_tag']);
            }
        };
        $this->channel->basic_qos(null, 1, null);
        $this->channel->basic_consume($this->queueName, '', false, false, false, false, $callback);
        while(count($this->channel->callbacks)) {
            $this->channel->wait();
        }
    }

    /**
     * @inheritdoc
     */
    protected function sendMessage($message, $timeout)
    {
        if ($timeout) {
            throw new NotSupportedException('Delayed work is not supported in the driver.');
        }

        $this->open();
        $this->channel->basic_publish(new AMQPMessage($message), $this->exchangeName);
    }

    /**
     * Opens connection and channel
     */
    protected function open()
    {
        if ($this->channel) return;
        $this->connection = new AMQPStreamConnection($this->host, $this->port, $this->user, $this->password);
        $this->channel = $this->connection->channel();
        $this->channel->queue_declare($this->queueName);
        $this->channel->exchange_declare($this->exchangeName, $this->exchangeType, false, true, false);
        $this->channel->queue_bind($this->queueName, $this->exchangeName);
    }

    /**
     * Closes connection and channel
     */
    protected function close()
    {
        if (!$this->channel) return;
        $this->channel->close();
        $this->connection->close();
    }
}