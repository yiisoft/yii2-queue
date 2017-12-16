DB драйвер
==========

DB дравер для хранения очереди заданий использует базу данных.

Пример настройки:

```php
return [
    'bootstrap' => [
        'queue', // Компонент регистрирует свои консольные команды 
    ],
    'components' => [
        'db' => [
            'class' => \yii\db\Connection::class, 
            // ...
        ],
        'queue' => [
            'class' => \yii\queue\db\Queue::class,
            'db' => 'db', // Компонент подключения к БД или его конфиг
            'tableName' => '{{%queue}}', // Имя таблицы
            'channel' => 'default', // Выбранный для очереди канал
            'mutex' => \yii\mutex\MysqlMutex::class, // Мьютекс для синхронизации запросов
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
  `pushed_at` int(11) NOT NULL,
  `ttr` int(11) NOT NULL,
  `delay` int(11) NOT NULL DEFAULT 0,
  `priority` int(11) unsigned NOT NULL DEFAULT 1024,
  `reserved_at` int(11) DEFAULT NULL,
  `attempt` int(11) DEFAULT NULL,
  `done_at` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `channel` (`channel`),
  KEY `reserved_at` (`reserved_at`),
  KEY `priority` (`priority`)
) ENGINE=InnoDB
```

Миграции смотрите в [src/drivers/db/migrations](../../src/drivers/db/migrations).

Расширение предлагает использовать
[миграции с неймспейсами](http://www.yiiframework.com/doc-2.0/guide-db-migrations.html#namespaced-migrations).
Чтобы добавить их в ваше приложение отредактируйте консольный конфиг:

```php
'controllerMap' => [
    // ...
    'migrate' => [
        'class' => 'yii\console\controllers\MigrateController',
        'migrationPath' => null,
        'migrationNamespaces' => [
            // ...
            'yii\queue\db\migrations',
        ],
    ],
],
```

Затем запустите команду:

```sh
yii migrate/up
```

Консоль
-------

Для обработки очереди используются консольные команды.

```sh
yii queue/listen [timeout]
```

Команда `listen` запускает обработку очереди в режиме демона. Очередь опрашивается непрерывно.
Если добавляются новые задания, то они сразу же извлекаются и выполняются. `timeout` - время
ожидания в секундах перед следующим опросом очереди. Способ наиболее эфективен если запускать
команду через [supervisor](worker.md#supervisor) или [systemd](worker.md#systemd).

```sh
yii queue/run
```

Команда `run` в цикле извлекает задания из очереди и выполняет их, пока очередь не опустеет, и
завершает свою работу. Это способ подойдет для обработки очереди заданий через
[cron](worker.md#cron).

Для команд `run` и `listen` доступны следующие опции:

- `--verbose`, `-v`: состояние обработки заданий выводится в консоль.
- `--isolate`: каждое задание выполняется в отдельном дочернем процессе.
- `--color`: подсветка вывода в режиме `--verbose`.

```sh
yii queue/info
```

Команда `info` выводит инофрмацию о состоянии очереди.

```sh
yii queue/clear
```

Команда `clear` делает полную очистку очереди.

```sh
yii queue/remove [id]
```

Команда `remove` удаляет задание из очереди.
