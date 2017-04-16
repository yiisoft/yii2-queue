Upgrading Instructions
======================

This file contains the upgrade notes. These notes highlight changes that could break your
application when you upgrade the package from one version to another.

Upgrade from 0.x to 1.0
-----------------------

* Some methods and constants was renamed.
  
  - Method `Job::run()` renamed to `Job::execute()`.
  - Const `Queue::EVENT_BEFORE_WORK` renamed to `Queue::EVENT_BEFORE_EXEC`.
  - Const `Queue::EVENT_AFTER_WORK` renamed to `Queue::EVENT_AFTER_EXEC`.
  - Const `Queue::EVENT_AFTER_ERROR` renamed to `Queue::EVENT_AFTER_EXEC_ERROR`.

* All drivers moved from `queue` to `queue\drivers` namespace.
  
  - Class `\zhuravljov\yii\queue\amqp\Queue` moved to `\zhuravljov\yii\queue\drivers\amqp\Queue`.
  - Class `\zhuravljov\yii\queue\beanstalk\Queue` moved to `\zhuravljov\yii\queue\drivers\beanstalk\Queue`.
  - Class `\zhuravljov\yii\queue\db\Queue` moved to `\zhuravljov\yii\queue\drivers\db\Queue`.
  - Class `\zhuravljov\yii\queue\gearman\Queue` moved to `\zhuravljov\yii\queue\drivers\gearman\Queue`.
  - Class `\zhuravljov\yii\queue\redis\Queue` moved to `\zhuravljov\yii\queue\drivers\redis\Queue`.
  - Class `\zhuravljov\yii\queue\sync\Queue` moved to `\zhuravljov\yii\queue\drivers\sync\Queue`.


Upgrade from 0.10.1
-------------------

* Driver property was removed and this functionality was moved into queue classes. If you use public
  methods of `Yii::$app->queue->driver` you need to use methods of `Yii::$app->queue`. 
  
  And you need to check your configs. For example, now config for db queue see:
  
  ```php
  'queue' => [
      'class' => \zhuravljov\yii\queue\db\Queue::class,
      'db' => 'db',
      'tableName' => '{{%queue}}',
      'channel' => 'default',
      'mutex' => \yii\mutex\MysqlMutex::class,
  ],
  ```
 
  Instead of old variant:
 
  ```php
  'queue' => [
      'class' => \zhuravljov\yii\queue\Queue::class,
      'driver' => [
          'class' => \zhuravljov\yii\queue\db\Driver::class,
          'db' => 'db',
          'tableName' => '{{%queue}}'
          'channel' => 'default',
          'mutex' => \yii\mutex\MysqlMutex::class,
      ],
  ],
  ```
  