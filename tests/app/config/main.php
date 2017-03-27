<?php
return [
    'id' => 'yii2-queue-app',
    'basePath' => dirname(__DIR__),
    'vendorPath' => dirname(dirname(dirname(__DIR__))) . '/vendor',
    'bootstrap' => [
        'mysqlQueue',
        'sqliteQueue',
        'redisQueue',
        'amqpQueue',
        'beanstalkQueue',
    ],
    'components' => [
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
            'class' => \zhuravljov\yii\queue\Queue::class,
            'driver' => [
                'class' => \zhuravljov\yii\queue\db\Driver::class,
                'db' => 'mysql',
                'mutex' => [
                    'class' => \yii\mutex\MysqlMutex::class,
                    'db' => 'mysql',
                ],
            ],
        ],
        'sqlite' => [
            'class' => \yii\db\Connection::class,
            'dsn' => 'sqlite:@runtime/yii2_queue_test.db',
        ],
        'sqliteQueue' => [
            'class' => \zhuravljov\yii\queue\Queue::class,
            'driver' => [
                'class' => \zhuravljov\yii\queue\db\Driver::class,
                'db' => 'sqlite',
                'mutex' => \yii\mutex\FileMutex::class,
            ],
        ],
        'redis' => [
            'class' => \yii\redis\Connection::class,
            'database' => 2,
        ],
        'redisQueue' => [
            'class' => \zhuravljov\yii\queue\Queue::class,
            'driver' => \zhuravljov\yii\queue\redis\Driver::class,
        ],
        'amqpQueue' => [
            'class' => \zhuravljov\yii\queue\Queue::class,
            'driver' => \zhuravljov\yii\queue\amqp\Driver::class,
        ],
        'beanstalkQueue' => [
            'class' => \zhuravljov\yii\queue\Queue::class,
            'driver' => \zhuravljov\yii\queue\beanstalk\Driver::class,
        ],
        'syncQueue' => [
            'class' => \zhuravljov\yii\queue\Queue::class,
            'driver' => \zhuravljov\yii\queue\sync\Driver::class,
        ],
    ],
];