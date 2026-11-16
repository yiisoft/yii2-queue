Upgrading Instructions
======================

This file contains the upgrade notes. These notes highlight changes that could break your
application when you upgrade the package from one version to another.

Upgrade to 3.0.0
----------------

### General

* The minimum required versions are now PHP `8.3`, `yiisoft/yii2` `2.0.54` and `symfony/process` `7.0`.

* The deprecated interfaces and classes were removed. Use their replacements instead:

  | Removed                             | Use instead                                  |
  |-------------------------------------|----------------------------------------------|
  | `yii\queue\Job`                     | `yii\queue\JobInterface`                     |
  | `yii\queue\RetryableJob`            | `yii\queue\RetryableJobInterface`            |
  | `yii\queue\serializers\Serializer`  | `yii\queue\serializers\SerializerInterface`  |
  | `yii\queue\cli\Signal`              | `yii\queue\cli\SignalLoop`                   |
  | `yii\queue\cli\Verbose`             | `yii\queue\cli\VerboseBehavior`              |

* The deprecated Amqp driver (`yii\queue\amqp\Queue`) was removed.
  Use [Amqp Interop](docs/guide/driver-amqp-interop.md) instead.

* All classes now declare `strict_types=1` and use native types for properties, parameters and
  return values. If you implement the package interfaces or extend its classes, update your
  signatures. The most common ones:

  ```php
  // yii\queue\JobInterface
  public function execute(Queue $queue);

  // yii\queue\RetryableJobInterface
  public function getTtr(): int;
  public function canRetry(int $attempt, ?Throwable $error): bool;

  // yii\queue\serializers\SerializerInterface
  public function serialize($job): string;
  public function unserialize(string $serialized);

  // yii\queue\cli\LoopInterface
  public function canContinue(): bool;

  // yii\queue\Queue (custom drivers)
  protected function pushMessage(string $payload, int $ttr, int $delay, mixed $priority): int|string|null;
  public function status(int|string $id): int;

  // yii\queue\cli\Command (custom drivers)
  protected function isWorkerAction(string $actionID): bool;
  ```

  The `*CountInterface` interfaces now return `int`.
  `StatisticsProviderInterface::getStatisticsProvider()` now returns the new
  `yii\queue\interfaces\StatisticsInterface`.

  If a subclass redeclares a property, it must use the same type as the parent.

* Configuration values must match the property types. For example, `ttr` and `attempts` must be
  integers. A value that cannot be converted now throws a `TypeError` instead of an
  `InvalidConfigException`.

* `Queue::$serializer` is no longer replaced with an object in `init()`, so it keeps the configured
  value (a class name, an array or an object). If your code calls `$queue->serializer->serialize()`
  directly, resolve it first:
  `Instance::ensure($queue->serializer, SerializerInterface::class)`.

* The event properties are typed now: `JobEvent::$id` is `string|int|null`, `ExecEvent::$error` is
  `?Throwable`, `ExecEvent::$attempt` is `int` and `ExecEvent::$retry` is `bool`. Event handlers
  must assign values of these types.

* `opis/closure` was replaced with `laravel/serializable-closure` to serialize closure jobs
  (`yii\queue\closure\Behavior`). Closure jobs pushed by 2.x cannot be executed by 3.0.
  Wait until all closure jobs are processed by the old workers before you deploy the upgrade.
  You can remove `opis/closure` from your `composer.json` if nothing else uses it.

* The driver dependencies were updated. If you use a driver, update its package in your
  `composer.json`:

  | Driver       | Package                                                                 |
  |--------------|-------------------------------------------------------------------------|
  | Beanstalk    | `pda/pheanstalk` `^8.0`                                                 |
  | AWS SQS      | `aws/aws-sdk-php` `^3.369`                                              |
  | Amqp Interop | `enqueue/amqp-lib`, `enqueue/amqp-ext` or `enqueue/amqp-bunny` `^0.10`  |
  | Stomp        | `enqueue/stomp` `^0.10`                                                 |
  | Redis        | `yiisoft/yii2-redis` `~2.1.0`                                           |

### DB driver

* The public property `M161119140200Queue::$tableOptions` was removed. If you extend the migration
  or set this property, pass the table options in your own migration instead.

### Beanstalk driver

* The driver now uses `pda/pheanstalk` `8.x` instead of `3.x`. If you extend the driver or call
  `getPheanstalk()` directly, update your code to the pheanstalk 8 API.

* Job IDs are strings now. `Queue::push()` returns `"123"` instead of `123`, and `ExecEvent::$id`
  is a string too. If you store the ID in an integer column or compare it with `===`, cast it to
  `int`.

* `Queue::getStatsTube()` returns a `Pheanstalk\Values\TubeStats` object instead of the pheanstalk 3
  array-like response. Use its properties instead of array keys, for example
  `$stats->currentJobsReady` instead of `$stats['current-jobs-ready']`.

### AWS SQS driver

* The protected methods `reserve()` and `delete()` work with a `yii\queue\sqs\Payload` object
  instead of an array. If you extend the driver, update your overrides.

Upgrade to 2.3.6
----------------

* The `maxPriority` property was removed from [amqp_interop](docs/guide/driver-amqp-interop.md). 
  Use property `queueOptionalArguments` argument `x-max-priority`.

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
