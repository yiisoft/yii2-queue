<?php

declare(strict_types=1);

use yii\db\Connection;
use yii\mutex\FileMutex;
use yii\mutex\MysqlMutex;
use yii\mutex\PgsqlMutex;
use yii\queue\amqp_interop\Queue as AmqpInteropQueue;
use yii\queue\beanstalk\Queue as BeanstalkQueue;
use yii\queue\db\Queue as DbQueue;
use yii\queue\file\Queue as FileQueue;
use yii\queue\gearman\Queue as GearmanQueue;
use yii\queue\redis\Queue as RedisQueue;
use yii\queue\sqs\Queue as SqsQueue;
use yii\queue\stomp\Queue as StompQueue;
use yii\queue\sync\Queue as SyncQueue;
use yii\redis\Connection as RedisConnection;

$config = [
    'id' => 'yii2-queue-app',
    'basePath' => dirname(__DIR__),
    'vendorPath' => dirname(__DIR__, 2) . '/vendor',
    'runtimePath' => dirname(__DIR__, 2) . '/runtime',
    'bootstrap' => [
        'fileQueue',
        'mysqlQueue',
        'sqliteQueue',
        'pgsqlQueue',
        'redisQueue',
        'amqpInteropQueue',
        'beanstalkQueue',
        'stompQueue',
        'sqsQueue',
        'sqsFifoQueue',
    ],
    'components' => [
        'syncQueue' => [
            'class' => SyncQueue::class,
        ],
        'fileQueue' => [
            'class' => FileQueue::class,
        ],
        'mysql' => [
            'class' => Connection::class,
            'dsn' => sprintf(
                'mysql:host=%s;dbname=%s',
                getenv('MYSQL_HOST') ?: 'localhost',
                getenv('MYSQL_DATABASE') ?: 'yii2_queue_test'
            ),
            'username' => getenv('MYSQL_USER') ?: 'root',
            'password' => getenv('MYSQL_PASSWORD') ?: '',
            'charset' => 'utf8',
            'attributes' => [
                PDO::MYSQL_ATTR_INIT_COMMAND => 'SET sql_mode = "STRICT_ALL_TABLES"',
            ],
        ],
        'mysqlQueue' => [
            'class' => DbQueue::class,
            'db' => 'mysql',
            'mutex' => [
                'class' => MysqlMutex::class,
                'db' => 'mysql',
            ],
        ],
        'sqlite' => [
            'class' => Connection::class,
            'dsn' => 'sqlite:@runtime/yii2_queue_test.db',
        ],
        'sqliteQueue' => [
            'class' => DbQueue::class,
            'db' => 'sqlite',
            'mutex' => FileMutex::class,
        ],
        'pgsql' => [
            'class' => Connection::class,
            'dsn' => sprintf(
                'pgsql:host=%s;dbname=%s',
                getenv('POSTGRES_HOST') ?: 'localhost',
                getenv('POSTGRES_DB') ?: 'yii2_queue_test'
            ),
            'username' => getenv('POSTGRES_USER') ?: 'postgres',
            'password' => getenv('POSTGRES_PASSWORD') ?: '',
            'charset' => 'utf8',
        ],
        'pgsqlQueue' => [
            'class' => DbQueue::class,
            'db' => 'pgsql',
            'mutex' => [
                'class' => PgsqlMutex::class,
                'db' => 'pgsql',
            ],
            'mutexTimeout' => 0,
        ],
        'redis' => [
            'class' => RedisConnection::class,
            'hostname' => getenv('REDIS_HOST') ?: 'localhost',
            'database' => getenv('REDIS_DB') ?: 1,
        ],
        'redisQueue' => [
            'class' => RedisQueue::class,
        ],
        'amqpInteropQueue' => [
            'class' => AmqpInteropQueue::class,
            'host' => getenv('RABBITMQ_HOST') ?: 'localhost',
            'user' => getenv('RABBITMQ_USER') ?: 'guest',
            'password' => getenv('RABBITMQ_PASSWORD') ?: 'guest',
            'port' => getenv('RABBITMQ_PORT') ?: 5672,
            'queueOptionalArguments' => ['x-max-priority' => 10],
            'queueName' => 'queue-interop',
            'exchangeName' => 'exchange-interop',
        ],
        'beanstalkQueue' => [
            'class' => BeanstalkQueue::class,
            'host' => getenv('BEANSTALK_HOST') ?: 'localhost',
        ],
        'stompQueue' => [
            'class' => StompQueue::class,
            'host' => getenv('ACTIVEMQ_HOST') ?: 'localhost',
        ],
        'sqsQueue' => [
            'class' => SqsQueue::class,
            'url' => getenv('AWS_SQS_URL'),
            'key' => getenv('AWS_KEY'),
            'secret' => getenv('AWS_SECRET'),
            'region' => getenv('AWS_REGION'),
        ],
        'sqsFifoQueue' => [
            'class' => SqsQueue::class,
            'url' => getenv('AWS_SQS_FIFO_URL'),
            'key' => getenv('AWS_KEY'),
            'secret' => getenv('AWS_SECRET'),
            'region' => getenv('AWS_REGION'),
            'messageGroupId' => getenv('AWS_SQS_FIFO_MESSAGE_GROUP_ID'),
        ],
    ],
];

if (defined('GEARMAN_SUCCESS')) {
    $config['bootstrap'][] = 'gearmanQueue';
    $config['components']['gearmanQueue'] = [
        'class' => GearmanQueue::class,
        'host' => getenv('GEARMAN_HOST') ?: 'localhost',
    ];
}

return $config;
