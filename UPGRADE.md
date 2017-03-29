Upgrading Instructions
======================

This file contains the upgrade notes. These notes highlight changes that could break your
application when you upgrade the package from one version to another.

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
  