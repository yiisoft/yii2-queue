<?php

declare(strict_types=1);

/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

namespace yii\queue\sqs;

use Aws\Credentials\CredentialProvider;
use Aws\Sqs\SqsClient;
use yii\base\NotSupportedException;
use yii\queue\cli\Queue as CliQueue;
use yii\queue\serializers\JsonSerializer;
use yii\queue\serializers\SerializerInterface;

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
    public string $url;
    /**
     * aws access key.
     * @var string|null
     */
    public ?string $key;
    /**
     * aws secret.
     * @var string|null
     */
    public ?string $secret;
    /**
     * region where queue is hosted.
     * @var string
     */
    public string $region = '';
    /**
     * API version.
     * @var string
     */
    public string $version = 'latest';
    /**
     * Message Group ID for FIFO queues.
     * @var string
     * @since 2.2.1
     */
    public string $messageGroupId = 'default';
    /**
     * @var string command class name
     * @inheritdoc
     */
    public string $commandClass = Command::class;
    /**
     * Json serializer by default.
     * @inheritdoc
     */
    public string|array|SerializerInterface $serializer = JsonSerializer::class;

    /**
     * @var SqsClient|null
     */
    private ?SqsClient $client = null;

    /**
     * Listens queue and runs each job.
     *
     * @param bool $repeat whether to continue listening when queue is empty.
     * @param int $timeout number of seconds to sleep before next iteration.
     * @return null|int exit code.
     * @internal for worker command only
     */
    public function run(bool $repeat, int $timeout = 0): ?int
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
    protected function reserve(int $timeout): ?array
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
        if ($ttr !== $this->ttr) {
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
    protected function delete(array $payload): void
    {
        $this->getClient()->deleteMessage([
            'QueueUrl' => $this->url,
            'ReceiptHandle' => $payload['ReceiptHandle'],
        ]);
    }

    /**
     * Clears the queue.
     */
    public function clear(): void
    {
        $this->getClient()->purgeQueue([
            'QueueUrl' => $this->url,
        ]);
    }

    /**
     * @inheritdoc
     */
    public function status($id): int
    {
        throw new NotSupportedException('Status is not supported in the driver.');
    }

    /**
     * Provides public access for `handleMessage`
     *
     * @param $id string
     * @param $message string
     * @param $ttr int
     * @param $attempt int
     * @return bool
     * @since 2.2.1
     */
    public function handle(string $id, string $message, int $ttr, int $attempt): bool
    {
        return $this->handleMessage($id, $message, $ttr, $attempt);
    }

    /**
     * @inheritdoc
     */
    protected function pushMessage(string $payload, int $ttr, int $delay, mixed $priority): int|string|null
    {
        if ($priority) {
            throw new NotSupportedException('Priority is not supported in this driver');
        }

        $request = [
            'QueueUrl' => $this->url,
            'MessageBody' => $payload,
            'DelaySeconds' => $delay,
            'MessageAttributes' => [
                'TTR' => [
                    'DataType' => 'Number',
                    'StringValue' => $ttr,
                ],
            ],
        ];

        if (str_ends_with($this->url, '.fifo')) {
            $request['MessageGroupId'] = $this->messageGroupId;
            $request['MessageDeduplicationId'] = hash('sha256', $payload);
        }

        $response = $this->getClient()->sendMessage($request);
        return $response['MessageId'];
    }

    /**
     * @return SqsClient
     */
    protected function getClient(): SqsClient
    {
        if (null !== $this->client) {
            return $this->client;
        }

        if ($this->key !== null && $this->secret !== null) {
            $credentials = [
                'key' => $this->key,
                'secret' => $this->secret,
            ];
        } else {
            // use default provider if no key and secret passed
            //see - https://docs.aws.amazon.com/aws-sdk-php/v3/guide/guide/credentials.html#credential-profiles
            $credentials = CredentialProvider::defaultProvider();
        }

        $this->client = new SqsClient([
            'credentials' => $credentials,
            'region' => $this->region,
            'version' => $this->version,
        ]);
        return $this->client;
    }
}
