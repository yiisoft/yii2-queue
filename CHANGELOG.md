Yii2 Queue Extension Change Log
===============================

## 2.0.1, November 13, 2017

- Bug #98: Fixed timeout error handler (zhuravljov)
- Bug #112: Queue command inside module (tsingsun)
- Bug #118: Synchronized moving of delayed and reserved jobs to waiting list (zhuravljov)
- Bug #155: Slave DB breaks listener (zhuravljov)
- Enh #97: `Queue::status` is public method (zhuravljov)
- Enh #116: Add Chinese Guide (kids-return)
- Enh #122: Rename `Job` to `JobInterface` (zhuravljov)
- Enh #137: All throwable errors caused by jobs are now caught (brandonkelly)
- Enh #141: Clear and remove commands for File, DB, Beanstalk and Redis drivers (zhuravljov)
- Enh #147: Igbinary job serializer (xutl)
- Enh #148: Allow to change vhost setting for RabbitMQ (ischenko)
- Enh #151: Compatibility with Yii 2.0.13 and PHP 7.2 (zhuravljov)
- Enh #160: Benchmark of job wait time (zhuravljov)
- Enh: Rename `cli\Verbose` behavior to `cli\VerboseBehavior` (zhuravljov)
- Enh: Rename `serializers\Serializer` interface to `serializers\SerializerInterface` (zhuravljov)
- Enh: Added `Signal::setExitFlag()` to stop `Queue::run()` loop manually (silverfire)

## 2.0.0

- Enh: The package is moved to yiisoft/yii2-queue (zhuravljov)

## 1.1.0

- Enh #50 Documentation about worker starting control (zhuravljov)
- Enh #70: Durability for rabbitmq queues (mkubenka)
- Enh: Detailed error about job type in message handling (zhuravljov)
- Enh #60: Enhanced event handling (zhuravljov)
- Enh: Job priority for DB driver (zhuravljov)
- Enh: File mode options of file driver (zhuravljov)
- Enh #47: Redis queue listen timeout (zhuravljov)
- Enh #23: Retryable jobs (zhuravljov)

## 1.0.1

- Enh #58: Deleting failed jobs from queue (zhuravljov)
- Enh #55: Job priority (zhuravljov)

## 1.0.0

- Enh: Improvements of log behavior (zhuravljov)
- Enh: File driver stat info (zhuravljov)
- Enh: Beanstalk stat info (zhuravljov)
- Enh: Colorized driver info actions (zhuravljov)
- Enh: Colorized verbose mode (zhuravljov)
- Enh: Improvements of debug panel (zhuravljov)
- Enh: Queue job message statuses (zhuravljov)
- Enh: Gii job generator (zhuravljov)
- Enh: Enhanced gearman driver (zhuravljov)
- Enh: Queue message identifiers (zhuravljov)
- Enh: File queue (zhuravljov)

## 0.12.2

- Enh #10: Separate option that turn off isolate mode of job execute (zhuravljov)

## 0.12.1

- Bug #37: Fixed opening of a child process (zhuravljov)
- Enh: Ability to push a closure (zhuravljov)
- Enh: Before push event (zhuravljov)

## 0.12.0

- Enh #18: Executes a job in a child process (zhuravljov)
- Bug #25: Enabled output buffer breaks output streams (luke-)
- Enh: After push event (zhuravljov)

## 0.11.0

- Enh #21: Delayed jobs for redis queue (zhuravljov)
- Enh: Info action for db and redis queue command (zhuravljov)

## 0.10.1

- Bug: Fixed db driver for pgsql (zhuravljov)
- Bug #16: Timeout of  queue reading lock for db driver (zhuravljov)
- Enh: Minor code style enhancements (SilverFire)

## 0.10.0

- Enh #14: Json job serializer (zhuravljov)
- Enh: Delayed running of a job (zhuravljov)

## 0.9.1

- Bug #13: Fixed reading of DB queue (zhuravljov)

## 0.9.0

- Enh: Signal handlers (zhuravljov)
- Enh: Add exchange for AMQP driver (airani)
- Enh: Beanstalk driver (zhuravljov)
- Enh: Added English docs (samdark)



