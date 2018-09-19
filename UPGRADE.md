Upgrading Instructions
======================

This file contains the upgrade notes. These notes highlight changes that could break your
application when you upgrade the package from one version to another.

Upgrade to 2.1.1
----------------

* `\yii\queue\ErrorEvent` has been deprecated and will be removed in `3.0`.
  Use `\yii\queue\ExecEvent` instead.

Upgrade from 2.0.1 to 2.0.2
---------------------------

* The [Amqp driver](docs/guide/driver-amqp.md) has been deprecated and will be removed in `2.1`.
  It is advised to migrate to [Amqp Interop](docs/guide/driver-amqp-interop.md) instead.

* Added `\yii\queue\cli\Command::isWorkerAction()` abstract method. If you use your own console
  controllers to run queue listeners, you must implement it.

* `\yii\queue\cli\Signal` helper is deprecated and will be removed in `2.1`.  The extension uses
  `\yii\queue\cli\SignalLoop` instead of the helper.

* If you use your own console controller to listen to a queue, you must upgrade it. See the native
  console controllers for how to upgrade.

Upgrade from 2.0.0 to 2.0.1
---------------------------

* `yii\queue\cli\Verbose` behavior was renamed to `yii\queue\cli\VerboseBehavior`. The old class was
  marked as deprecated and will be removed in `2.1.0`.

* `Job`, `RetryableJob` and `Serializer` interfaces were renamed to `JobInterface`,
  `RetryableJobInterface` and `SerializerInterface`. The old names are declared as deprecated
  and will be removed in `2.1.0`.

Upgrade from 1.1.0 to 2.0.0
---------------------------

* Code has been moved to yii namespace. Check and replace `zhuravljov\yii` to `yii` namespace for
  your project.

Upgrade from 1.0.0 to 1.1.0
---------------------------

* Event `Queue::EVENT_AFTER_EXEC_ERROR` renamed to `Queue::EVENT_AFTER_ERROR`.

* Removed method `Queue::later()`. Use method chain `Yii::$app->queue->delay(60)->push()` instead.

* Changed table schema for DB driver. Apply migration.


Upgrade from 0.x to 1.0.0
-------------------------

* Some methods and constants were modified.

  - Method `Job::run()` modified to `Job::execute($queue)`.
  - Const `Queue::EVENT_BEFORE_WORK` renamed to `Queue::EVENT_BEFORE_EXEC`.
  - Const `Queue::EVENT_AFTER_WORK` renamed to `Queue::EVENT_AFTER_EXEC`.
  - Const `Queue::EVENT_AFTER_ERROR` renamed to `Queue::EVENT_AFTER_EXEC_ERROR`.

* Method `Queue::sendMessage` renamed to `Queue::pushMessage`. Check it if you use it in your own
  custom drivers.


Upgrade from 0.10.1
-------------------

* Driver property was removed and this functionality was moved into queue classes. If you use public
  methods of `Yii::$app->queue->driver` you need to use the methods of `Yii::$app->queue`.

  You also need to check your configs. For example, now the config for the db queue is:

  ```php
  'queue' => [
      'class' => \zhuravljov\yii\queue\db\Queue::class,
      'db' => 'db',
      'tableName' => '{{%queue}}',
      'channel' => 'default',
      'mutex' => \yii\mutex\MysqlMutex::class,
  ],
  ```

  Instead of the old variant:

  ```php
  'queue' => [
      'class' => \zhuravljov\yii\queue\Queue::class,
      'driver' => [
          'class' => \yii\queue\db\Driver::class,
          'db' => 'db',
          'tableName' => '{{%queue}}'
          'channel' => 'default',
          'mutex' => \yii\mutex\MysqlMutex::class,
      ],
  ],
  ```
