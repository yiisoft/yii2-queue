Yii2 Queue Extension Change Log
===============================

## 1.1.0

- Enh #50 Documentation about worker starting control
- Enh #70: Durability for rabbitmq queues (mkubenka)
- Enh: Detailed error about job type in message handling
- Enh #60: Enhanced event handling
- Enh: Job priority for DB driver
- Enh: File mode options of file driver
- Enh #47: Redis queue listen timeout
- Enh #23: Retryable jobs

## 1.0.1

- Enh #58: Deleting failed jobs from queue
- Enh #55: Job priority

## 1.0.0

- Enh: Improvements of log behavior
- Enh: File driver stat info
- Enh: Beanstalk stat info
- Enh: Colorized driver info actions
- Enh: Colorized verbose mode
- Enh: Improvements of debug panel
- Enh: Queue job message statuses
- Enh: Gii job generator
- Enh: Enhanced gearman driver
- Enh: Queue message identifiers
- Enh: File queue

## 0.12.2

- Enh #10: Separate option that turn off isolate mode of job execute

## 0.12.1

- Bug #37: Fixed opening of a child process
- Enh: Ability to push a closure
- Enh: Before push event

## 0.12.0

- Enh #18: Executes a job in a child process
- Bug #25: Enabled output buffer breaks output streams (luke-)
- Enh: After push event 

## 0.11.0

- Enh #21: Delayed jobs for redis queue
- Enh: Info action for db and redis queue command

## 0.10.1

- Bug: Fixed db driver for pgsql
- Bug #16: Timeout of  queue reading lock for db driver
- Enh: Minor code style enhancements (SilverFire)

## 0.10.0

- Enh #14: Json job serializer
- Enh: Delayed running of a job

## 0.9.1

- Bug #13: Fixed reading of DB queue

## 0.9.0

- Enh: Signal handlers
- Enh: Add exchange for AMQP driver (airani)
- Enh: Beanstalk driver
- Enh: Added English docs (samdark)
