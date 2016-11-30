DB драйвер
==========

DB дравер для хранения очереди заданий использует базу данных.

Пример настройки:

```php
return [
    'components' => [
        'queue' => [
            'class' => \zhuravljov\yii\queue\Queue::class,
            'driver' => [
                'class' => \zhuravljov\yii\queue\db\Driver::class,
                'db' => 'db', // ID подключения
                'tableName' => '{{%queue}}', // таблица
                'mutex' => \yii\mutex\MysqlMutex::class, // мьютекс для синхронизации запросов
            ],
        ],
    ],
];
```

В базу данных нужно добавить таблицу. Схема, на примере MySQL:

```SQL
CREATE TABLE `queue` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `channel` varchar(255) NOT NULL,
  `job` blob NOT NULL,
  `created_at` int(11) NOT NULL,
  `started_at` int(11) DEFAULT NULL,
  `finished_at` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `channel` (`channel`),
  KEY `started_at` (`started_at`)
) ENGINE=InnoDB
```

Миграции смотрите в [src/db/migrations](../../src/db/migrations).

Консоль
-------

Команда `channels` показывате список используемых каналов, и количество необработанных задач в
каждом из них: 

```bash
yii queue/channels
```

Для выполнения задач также используются консольные команды.

```bash
yii queue/run channel
```

Команда `run` в цикле извлекает задания из очереди и выполняет их, пока очередь не опустеет, и
завершает свою работу. `channel` - выбранный канал. Это способ подойдет для обработки очереди
заданий через cron.

```bash
yii queue/listen channel [delay]
```

Команда `listen` запускает обработку очереди в режиме демона. Очередь опрашивается непрерывно.
Если добавляются новые задания, то они сразу же извлекаются и выполняются. `channel` - выбранный
канал. `delay` - время ожидания в секундах перед следующим опросом очереди. Способ наиболее
эфективен если запускать команду через демон-супервизор, например `supervisord`.

```bash
yii queue/purge channel
```

Команда `purge` чистит очередь из выбранного канала `channel`.
