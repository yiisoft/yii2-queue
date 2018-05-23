<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\queue\sqs;

use Aws\Credentials\CredentialProvider;
use Aws\Sqs\SqsClient;
use yii\base\NotSupportedException;
use yii\queue\cli\Queue as CliQueue;
use yii\queue\serializers\JsonSerializer;

/**
 * SQS Queue.
 *
 * @author Max Kozlovsky <kozlovskymaxim@gmail.com>
 * @author Manoj Malviya <manojm@girnarsoft.com>
 */
class Queue extends CliQueue
{
    /**
     * The SQS url.
     * @var string
     */
    public $url;
    /**
     * aws access key.
     * @var string|null
     */
    public $key;
    /**
     * aws secret.
     * @var string|null
     */
    public $secret;
    /**
     * region where queue is hosted.
     * @var string
     */
    public $region = '';
    /**
     * API version.
     * @var string
     */
    public $version = 'latest';
    /**
     * @var string command class name
     * @inheritdoc
     */
    public $commandClass = Command::class;
    /**
     * Json serializer by default.
     * @inheritdoc
     */
    public $serializer = JsonSerializer::class;

    /**
     * @var SqsClient
     */
    private $_client;


    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
    }

    /**
     * Listens queue and runs each job.
     *
     * @param bool $repeat whether to continue listening when queue is empty.
     * @param int $timeout number of seconds to sleep before next iteration.
     * @return null|int exit code.
     * @internal for worker command only
     */
    public function run($repeat, $timeout = 0)
    {
        return $this->runWorker(function (callable $canContinue) use ($repeat, $timeout) {
            while ($canContinue()) {
                if (($payload = $this->reserve($timeout)) !== null) {
                    $id = $payload['MessageId'];
                    $message = $payload['Body'];
                    $ttr = (int) $payload['MessageAttributes']['TTR']['StringValue'];
                    $attempt = (int) $payload['Attributes']['ApproximateReceiveCount'];
                    if ($this->handleMessage($id, $message, $ttr, $attempt)) {
                        $this->delete($payload);
                    }
                } elseif (!$repeat) {
                    break;
                }
            }
        });
    }

    /**
     * Gets a single message from SQS queue and sets the visibility to reserve message.
     *
     * @param int $timeout number of seconds for long polling. Must be between 0 and 20.
     * @return null|array payload.
     */
    protected function reserve($timeout)
    {
        $response = $this->getClient()->receiveMessage([
            'QueueUrl' => $this->url,
            'AttributeNames' => ['ApproximateReceiveCount'],
            'MessageAttributeNames' => ['TTR'],
            'MaxNumberOfMessages' => 1,
            'VisibilityTimeout' => $this->ttr,
            'WaitTimeSeconds' => $timeout,
        ]);
        if (!$response['Messages']) {
            return null;
        }

        $payload = reset($response['Messages']);

        $ttr = (int) $payload['MessageAttributes']['TTR']['StringValue'];
        if ($ttr != $this->ttr) {
            $this->getClient()->changeMessageVisibility([
                'QueueUrl' => $this->url,
                'ReceiptHandle' => $payload['ReceiptHandle'],
                'VisibilityTimeout' => $ttr,
            ]);
        }

        return $payload;
    }

    /**
     * Deletes the message after successfully handling.
     *
     * @param array $payload
     */
    protected function delete($payload)
    {
        $this->getClient()->deleteMessage([
            'QueueUrl' => $this->url,
            'ReceiptHandle' => $payload['ReceiptHandle'],
        ]);
    }

    /**
     * Clears the queue.
     */
    public function clear()
    {
        $this->getClient()->purgeQueue([
            'QueueUrl' => $this->url,
        ]);
    }

    /**
     * @inheritdoc
     */
    public function status($id)
    {
        throw new NotSupportedException('Status is not supported in the driver.');
    }

    /**
     * @inheritdoc
     */
    protected function pushMessage($message, $ttr, $delay, $priority)
    {
        if ($priority) {
            throw new NotSupportedException('Priority is not supported in this driver');
        }

        $response = $this->getClient()->sendMessage([
            'QueueUrl' => $this->url,
            'MessageBody' => $message,
            'DelaySeconds' => $delay,
            'MessageAttributes' => [
                'TTR' => [
                    'DataType' => 'Number',
                    'StringValue' => $ttr,
                ],
            ],
        ]);
        return $response['MessageId'];
    }

    /**
     * @return \Aws\Sqs\SqsClient
     */
    protected function getClient()
    {
        if ($this->_client) {
            return $this->_client;
        }

        if ($this->key !== null && $this->secret !== null) {
            $credentials = [
                'key' => $this->key,
                'secret' => $this->secret,
            ];
        } else {
            // use default provider if no key and secret passed
            //see - http://docs.aws.amazon.com/aws-sdk-php/v3/guide/guide/credentials.html#credential-profiles
            $credentials = CredentialProvider::defaultProvider();
        }

        $this->_client = new SqsClient([
            'credentials' => $credentials,
            'region' => $this->region,
            'version' => $this->version,
        ]);
        return $this->_client;
    }
}
