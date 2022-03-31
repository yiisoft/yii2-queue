Yii2 Queue Extension Change Log
===============================

2.3.4 March 31, 2022
--------------------

- Enh #449: Force db to use the index on the `reserved_at` column to unlock unfinished tasks in DB driver (erickskrauch)


2.3.3 December 30, 2021
-----------------------

- Enh #257: Increase MySQL db job size to more than 65KB (lourdas)
- Enh #394: Added stack trace on error in verbose mode (germanow)
- Enh #405: Change access modifier of `moveExpired` in DB drivers (matiosfree)
- Enh #427: Added configurable AMQP `routingKey` options (alisin, s1lver)
- Enh #430: Added configurable AMQP Exchange type (s1lver)
- Enh #435: Added the ability to set optional arguments for the AMQP queue (s1lver)
- Enh #445: Display memory peak usage when verbose output is enabled (nadar)


2.3.2 May 05, 2021
------------------

- Bug #414: Fixed PHP errors when PCNTL functions were disallowed (brandonkelly)


2.3.1 December 23, 2020
-----------------------

- Bug #380: Fixed amqp-interop queue/listen signal handling (tarinu)
- Enh #388: `symfony/process 5.0` compatibility (leandrogehlen)


2.3.0 June 04, 2019
-------------------

- Enh #260: Added STOMP driver (versh23)


2.2.1 May 21, 2019
------------------

- Bug #220: Updated to the latest amqp-lib (alexkart)
- Enh #293: Add `handle` method to `\yii\queue\sqs\Queue` that provides public access for `handleMessage` which can be
useful for handling jobs by webhooks (alexkart)
- Enh #332: Add AWS SQS FIFO support (kringkaste, alexkart)


2.2.0 Mar 20, 2019
------------------

- Bug #220: Fixed deadlock problem of DB driver (zhuravljov)
- Bug #258: Worker in isolated mode fails if PHP_BINARY contains spaces (luke-)
- Bug #267: Fixed symfony/process incompatibility (rob006)
- Bug #269: Handling of broken messages that are not unserialized correctly (zhuravljov)
- Bug #299: Queue config param validation (zhuravljov)
- Enh #248: Reduce roundtrips to beanstalk server when removing job (SamMousa)
- Enh #318: Added check result call function flock (evaldemar)
- Enh: Job execution result is now forwarded to the event handler (zhuravljov)
- Enh: `ErrorEvent` was marked as deprecated (zhuravljov)

2.1.0 May 24, 2018
------------------

- Bug #126: Handles a fatal error of the job execution in isolate mode (zhuravljov)
- Bug #207: Console params validation (zhuravljov)
- Bug #210: Worker option to define php bin path to run child process (zhuravljov)
- Bug #224: Invalid identifier "DELAY" (lar-dragon)
- Enh #192: AWS SQS implementation (elitemaks, manoj-girnar)
- Enh: Worker loop event (zhuravljov)

2.0.2 December 26, 2017
-----------------------

- Bug #92: Resolve issue in debug panel (farmani-eigital)
- Bug #99: Retry connecting after connection has timed out for redis driver (cebe)
- Bug #180: Fixed info command of file driver (victorruan)
- Enh #158: Add Amqp Interop driver (makasim)
- Enh #185: Loop object instead of Signal helper (zhuravljov)
- Enh #188: Configurable verbose mode (zhuravljov)
- Enh: Start and stop events of a worker (zhuravljov)

2.0.1 November 13, 2017
-----------------------

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

2.0.0 July 15, 2017
-------------------

- Enh: The package is moved to yiisoft/yii2-queue (zhuravljov)

1.1.0 July 12, 2017
-------------------

- Enh #50 Documentation about worker starting control (zhuravljov)
- Enh #70: Durability for rabbitmq queues (mkubenka)
- Enh: Detailed error about job type in message handling (zhuravljov)
- Enh #60: Enhanced event handling (zhuravljov)
- Enh: Job priority for DB driver (zhuravljov)
- Enh: File mode options of file driver (zhuravljov)
- Enh #47: Redis queue listen timeout (zhuravljov)
- Enh #23: Retryable jobs (zhuravljov)

1.0.1 June 7, 2017
------------------

- Enh #58: Deleting failed jobs from queue (zhuravljov)
- Enh #55: Job priority (zhuravljov)

1.0.0 May 4, 2017
-----------------

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

0.12.2 April 29, 2017
---------------------

- Enh #10: Separate option that turn off isolate mode of job execute (zhuravljov)

0.12.1 April 20, 2017
---------------------

- Bug #37: Fixed opening of a child process (zhuravljov)
- Enh: Ability to push a closure (zhuravljov)
- Enh: Before push event (zhuravljov)

0.12.0 April 14, 2017
---------------------

- Enh #18: Executes a job in a child process (zhuravljov)
- Bug #25: Enabled output buffer breaks output streams (luke-)
- Enh: After push event (zhuravljov)

0.11.0 April 2, 2017
--------------------

- Enh #21: Delayed jobs for redis queue (zhuravljov)
- Enh: Info action for db and redis queue command (zhuravljov)

0.10.1 March 29, 2017
---------------------

- Bug: Fixed db driver for pgsql (zhuravljov)
- Bug #16: Timeout of  queue reading lock for db driver (zhuravljov)
- Enh: Minor code style enhancements (SilverFire)

0.10.0 March 22, 2017
---------------------

- Enh #14: Json job serializer (zhuravljov)
- Enh: Delayed running of a job (zhuravljov)

0.9.1 March 6, 2017
-------------------

- Bug #13: Fixed reading of DB queue (zhuravljov)

0.9.0 March 6, 2017
-------------------

- Enh: Signal handlers (zhuravljov)
- Enh: Add exchange for AMQP driver (airani)
- Enh: Beanstalk driver (zhuravljov)
- Enh: Added English docs (samdark)
