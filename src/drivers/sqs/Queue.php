<?php

namespace yii\queue\sqs;

use yii\base\InvalidParamException;
use yii\base\NotSupportedException;
use yii\queue\cli\LoopInterface;
use yii\queue\cli\Queue as CliQueue;
use yii\queue\cli\Signal;
use \Aws\Sqs\SqsClient;
use Aws\Credentials\CredentialProvider;

/**
 * SQS Queue
 *
 * @author Manoj Malviya <manojm@girnarsoft.com>
 */
class Queue extends CliQueue
{
    /**
    * @var SqsClient
    */
    private $_client;

    /**
     * The SQS url.
     * @var string
     */
    public $url;

    /**
     * aws access key
     * @var string
     */
    public $key = '';

    /**
     * aws secret
     * @var string
     */
    public $secret = '';

    /**
     * region where queue is hosted.
     * @var string
     */
    public $region = '';

    /**
     * API version
     * @var string
     */
    public $version = 'latest';

    /**
     * @var string command class name
     */
    public $commandClass = Command::class;

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
        return $this->runWorker(function (LoopInterface $loop) use ($repeat, $timeout) {
            while ($loop->canContinue()) {
                if ($payload = $this->getPayload($timeout)) {
                    list($ttr, $message) = explode(';', $payload['Body'], 2);
                    //reserve it so it is not visible to another worker till ttr
                    $this->reserve($payload, $ttr);

                    if ($this->handleMessage(null, $message, $ttr, 1)) {
                        $this->release($payload);
                    }
                } elseif (!$repeat) {
                    break;
                }
            }
        });
    }

    /**
     * Clears the queue
     */
    public function clear()
    {
        $this->getClient()->purgeQueue(array(
            'QueueUrl' => $this->url,
        ));
    }

    /**
     * @inheritdoc
     */
    protected function pushMessage($message, $ttr, $delay, $priority)
    {
        if ($priority) {
            throw new NotSupportedException('Priority is not supported in this driver');
        }

        $model = $this->getClient()->sendMessage([
            'DelaySeconds' => $delay,
            'QueueUrl' => $this->url,
            'MessageBody' => "$ttr;$message",
        ]);

        return $model['MessageId'];
    }

    /**
     * @inheritdoc
     */
    public function status($id)
    {
        throw new NotSupportedException('Status is not supported in the driver.');
    }

    /**
     * @return \Aws\Sqs\SqsClient
     */
    protected function getClient()
    {
        if ($this->key && $this->secret) {
            $provider = [
                'key'    => $this->key, 
                'secret' => $this->secret
            ];
        } else {
            // use default provider if no key and secret passed
            //see - http://docs.aws.amazon.com/aws-sdk-php/v3/guide/guide/credentials.html#credential-profiles
            $provider = CredentialProvider::defaultProvider();
        }
        
        $config = [
            'credentials' => $provider,
            'region' => $this->region,
            'version' => $this->version,
        ];

        if (!$this->_client) {
            $this->_client = SqsClient::factory($config);
        }

        return $this->_client;
    }

    /**
    * Gets a single message from SQS queue
    *
    * @param int $timeout number of seconds for long polling. Must be between 0 and 20.
    * @return null|array payload.
    */
    private function getPayload($timeout = 0)
    {
        if ($timeout < 0 || $timeout > 20) {
            throw new InvalidParamException('Timeout must be between 0 and 20');
        }
        $payload = $this->getClient()->receiveMessage([
            'QueueUrl' => $this->url,
            'AttributeNames' => ['ApproximateReceiveCount'],
            'MaxNumberOfMessages' => 1,
            'WaitTimeSeconds' => $timeout,
        ]);

        $payload = $payload['Messages'];
        if ($payload) {
            return array_pop($payload);
        }

        return null;
    }

    /**
    * Set the visibility to reserve message
    * So that no other worker can see this message
    *
    * @param array $payload
    * @param int $ttr  
    */
    private function reserve($payload, $ttr)
    {
        $receiptHandle = $payload['ReceiptHandle'];
        $this->getClient()->changeMessageVisibility(array(
            'QueueUrl' => $this->url,
            'ReceiptHandle' => $receiptHandle,
            'VisibilityTimeout' => $ttr
        ));
    }

    /**
    * Mark the message as handled
    *
    * @param array $payload
    * @return boolean
    */
    private function release($payload)
    {
        if (empty($payload['ReceiptHandle'])) {
            return false;
        }

        $receiptHandle = $payload['ReceiptHandle'];
        $response = $this->getClient()->deleteMessage([
            'QueueUrl'      => $this->url,
            'ReceiptHandle' => $receiptHandle,
        ]);

        return $response !== null;
    }
}