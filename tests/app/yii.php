#!/usr/bin/env php
<?php
defined('YII_DEBUG') or define('YII_DEBUG', true);
defined('YII_ENV') or define('YII_ENV', 'test');

require(__DIR__ . '/../../vendor/autoload.php');
require(__DIR__ . '/../../vendor/yiisoft/yii2/Yii.php');

Yii::setAlias('@zhuravljov/yii/queue', dirname(dirname(__DIR__)) . '/src');
Yii::setAlias('@tests', dirname(__DIR__));

$config = \yii\helpers\ArrayHelper::merge(
    require(__DIR__ . '/config/main.php'),
    require(__DIR__ . '/config/console.php')
);

$app = new \yii\console\Application($config);
$code = $app->run();
exit($code);
