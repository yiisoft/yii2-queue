<?php

define('YII_DEBUG', true);
$_SERVER['SCRIPT_NAME'] = '/' . __DIR__;
$_SERVER['SCRIPT_FILENAME'] = __FILE__;

require_once(__DIR__ . '/../vendor/autoload.php');
require_once(__DIR__ . '/../vendor/yiisoft/yii2/Yii.php');

Yii::setAlias('@yii/queue', dirname(__DIR__) . '/src');
Yii::setAlias('@yii/queue/amqp', dirname(__DIR__) . '/src/drivers/amqp');
Yii::setAlias('@yii/queue/amqp_interop', dirname(__DIR__) . '/src/drivers/amqp_interop');
Yii::setAlias('@yii/queue/beanstalk', dirname(__DIR__) . '/src/drivers/beanstalk');
Yii::setAlias('@yii/queue/db', dirname(__DIR__) . '/src/drivers/db');
Yii::setAlias('@yii/queue/file', dirname(__DIR__) . '/src/drivers/file');
Yii::setAlias('@yii/queue/gearman', dirname(__DIR__) . '/src/drivers/gearman');
Yii::setAlias('@yii/queue/redis', dirname(__DIR__) . '/src/drivers/redis');
Yii::setAlias('@yii/queue/sync', dirname(__DIR__) . '/src/drivers/sync');
Yii::setAlias('@yii/queue/sqs', dirname(__DIR__) . '/src/drivers/sqs');
Yii::setAlias('@yii/queue/stomp', dirname(__DIR__) . '/src/drivers/stomp');
Yii::setAlias('@tests', __DIR__);

$config = require(__DIR__ . '/app/config/main.php');
$app = new \yii\console\Application($config);
