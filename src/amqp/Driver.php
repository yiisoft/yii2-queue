<?php
/**
 * @link https://github.com/zhuravljov/yii2-queue
 * @copyright Copyright (c) 2017 Roman Zhuravlev
 * @license http://opensource.org/licenses/BSD-3-Clause
 */

namespace zhuravljov\yii\queue\amqp;

use Yii;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use yii\base\Application;
use yii\base\BootstrapInterface;
use yii\base\Event;
use yii\base\NotSupportedException;
use yii\helpers\Inflector;
use zhuravljov\yii\queue\Driver as BaseDriver;

/**
 * AMQP Driver
 *
 * @author Roman Zhuravlev <zhuravljov@gmail.com>
 */
class Driver extends BaseDriver implements BootstrapInterface
{
    const TYPE_EXCHANGE_DIRECT = 'direct';
    const TYPE_EXCHANGE_TOPIC = 'topic';
    const TYPE_EXCHANGE_FANOUT = 'fanout';

    public $host = 'localhost';
    public $port = 5672;
    public $user = 'guest';
    public $password = 'guest';
    public $queueName = 'queue';
    public $exchangeName = 'exchange';
    public $exchangeType = self::TYPE_EXCHANGE_DIRECT;

    /**
     * @var AMQPStreamConnection
     */
    private $_connection;
    /**
     * @var AMQPChannel
     */
    private $_channel;

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
        Event::on(Application::class, Application::EVENT_AFTER_REQUEST, function () {
            $this->close();
        });
    }

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
        $this->open();
        $message = new AMQPMessage($this->serialize($job));
        $this->_channel->basic_publish($message, $this->exchangeName);
    }

    /**
     * @inheritdoc
     */
    public function later($job, $timeout)
    {
        throw new NotSupportedException('Delayed work is not supported in the driver.');
    }

    /**
     * Listens amqp-queue and runs new jobs.
     */
    public function listen()
    {
        $this->open();
        $callback = function(AMQPMessage $message) {
            $job = $this->unserialize($message->body);
            if ($this->getQueue()->run($job)) {
                $message->delivery_info['channel']->basic_ack($message->delivery_info['delivery_tag']);
            }
        };
        $this->_channel->basic_qos(null, 1, null);
        $this->_channel->basic_consume($this->queueName, '', false, false, false, false, $callback);
        while(count($this->_channel->callbacks)) {
            $this->_channel->wait();
        }
    }

    /**
     * Opens connection and channel
     */
    protected function open()
    {
        if ($this->_channel) return;
        $this->_connection = new AMQPStreamConnection($this->host, $this->port, $this->user, $this->password);
        $this->_channel = $this->_connection->channel();
        $this->_channel->queue_declare($this->queueName);
        $this->_channel->exchange_declare($this->exchangeName, $this->exchangeType, false, true, false);
        $this->_channel->queue_bind($this->queueName, $this->exchangeName);
    }

    /**
     * Closes connection and channel
     */
    protected function close()
    {
        if (!$this->_channel) return;
        $this->_channel->close();
        $this->_connection->close();
    }
}