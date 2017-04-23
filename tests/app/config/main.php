<?php
return [
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
        'beanstalkQueue',
    ],
    'components' => [
        'syncQueue' => [
            'class' => \zhuravljov\yii\queue\drivers\sync\Queue::class,
        ],
        'fileQueue' => [
            'class' => \zhuravljov\yii\queue\drivers\file\Queue::class,
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
            'class' => \zhuravljov\yii\queue\drivers\db\Queue::class,
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
            'class' => \zhuravljov\yii\queue\drivers\db\Queue::class,
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
            'class' => \zhuravljov\yii\queue\drivers\db\Queue::class,
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
            'class' => \zhuravljov\yii\queue\drivers\redis\Queue::class,
        ],
        'amqpQueue' => [
            'class' => \zhuravljov\yii\queue\drivers\amqp\Queue::class,
        ],
        'beanstalkQueue' => [
            'class' => \zhuravljov\yii\queue\drivers\beanstalk\Queue::class,
        ],
    ],
];