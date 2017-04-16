DB драйвер
==========

DB дравер для хранения очереди заданий использует базу данных.

Пример настройки:

```php
return [
    'bootstrap' => ['queue'],
    'components' => [
        'queue' => [
            'class' => \zhuravljov\yii\queue\drivers\db\Queue::class,
            'db' => 'db', // ID подключения
            'tableName' => '{{%queue}}', // таблица
            'channel' => 'default', // выбранный для очереди канал
            'mutex' => \yii\mutex\MysqlMutex::class, // мьютекс для синхронизации запросов
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
  `timeout` int(11) NOT NULL,
  `started_at` int(11) DEFAULT NULL,
  `finished_at` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `channel` (`channel`),
  KEY `started_at` (`started_at`)
) ENGINE=InnoDB
```

Миграции смотрите в [src/drivers/db/migrations](../../src/drivers/db/migrations).

Консоль
-------

Для выполнения задач также используются консольные команды.

```bash
yii queue/run
```

Команда `run` в цикле извлекает задания из очереди и выполняет их, пока очередь не опустеет, и
завершает свою работу. Это способ подойдет для обработки очереди заданий через cron.

```bash
yii queue/listen [delay]
```

Команда `listen` запускает обработку очереди в режиме демона. Очередь опрашивается непрерывно.
Если добавляются новые задания, то они сразу же извлекаются и выполняются. `delay` - время ожидания
в секундах перед следующим опросом очереди. Способ наиболее эфективен если запускать команду через
демон-супервизор, например `supervisord`.

```bash
yii queue/info
```

Команда `info` выводит инофрмацию о состоянии очереди.
