<?php
$config = [
    'id' => 'yii2-queue-app',
    'basePath' => dirname(__DIR__),
    'vendorPath' => dirname(dirname(dirname(__DIR__))) . '/vendor',
    'bootstrap' => [
        'fileQueue',
        'mysqlQueue',
        'sqliteQueue',
        'pgsqlQueue',
        'redisQueue',
        'amqpQueue',
        'amqpInteropQueue',
        'beanstalkQueue',
    ],
    'components' => [
        'syncQueue' => [
            'class' => \yii\queue\sync\Queue::class,
        ],
        'fileQueue' => [
            'class' => \yii\queue\file\Queue::class,
        ],
        'mysql' => [
            'class' => \yii\db\Connection::class,
            'dsn' => 'mysql:host=localhost;dbname=yii2_queue_test',
            'username' => 'root',
            'password' => '',
            'charset' => 'utf8',
            'attributes' => [
                PDO::MYSQL_ATTR_INIT_COMMAND => 'SET sql_mode = "STRICT_ALL_TABLES"',
            ],
        ],
        'mysqlQueue' => [
            'class' => \yii\queue\db\Queue::class,
            'db' => 'mysql',
            'mutex' => [
                'class' => \yii\mutex\MysqlMutex::class,
                'db' => 'mysql',
            ],
        ],
        'sqlite' => [
            'class' => \yii\db\Connection::class,
            'dsn' => 'sqlite:@runtime/yii2_queue_test.db',
        ],
        'sqliteQueue' => [
            'class' => \yii\queue\db\Queue::class,
            'db' => 'sqlite',
            'mutex' => \yii\mutex\FileMutex::class,
        ],
        'pgsql' => [
            'class' => \yii\db\Connection::class,
            'dsn' => 'pgsql:host=localhost;dbname=yii2_queue_test',
            'username' => 'postgres',
            'password' => '',
            'charset' => 'utf8',
        ],
        'pgsqlQueue' => [
            'class' => \yii\queue\db\Queue::class,
            'db' => 'pgsql',
            'mutex' => [
                'class' => \yii\mutex\PgsqlMutex::class,
                'db' => 'pgsql',
            ],
            'mutexTimeout' => 0,
        ],
        'redis' => [
            'class' => \yii\redis\Connection::class,
            'database' => 2,
        ],
        'redisQueue' => [
            'class' => \yii\queue\redis\Queue::class,
        ],
        'amqpQueue' => [
            'class' => \yii\queue\amqp\Queue::class,
        ],
        'amqpInteropQueue' => [
            'class' => \yii\queue\amqp_interop\Queue::class,
        ],
        'beanstalkQueue' => [
            'class' => \yii\queue\beanstalk\Queue::class,
        ],
    ],
];

if (defined('GEARMAN_SUCCESS')) {
    $config['bootstrap'][] = 'gearmanQueue';
    $config['components']['gearmanQueue'] = [
        'class' => \yii\queue\gearman\Queue::class,
    ];
}

return $config;