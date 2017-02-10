Gearman драйвер
===============

Драйвер работает с очередью на базе Gearman.

Пример настройки:

```php
return [
    'bootstrap' => ['queue'],
    'components' => [
        'queue' => [
            'class' => \zhuravljov\yii\queue\Queue::class,
            'driver' => [
                'class' => \zhuravljov\yii\queue\gearman\Driver::class,
                'host' => 'localhost',
                'port' => 4730,
                'channel' => 'my_queue',
            ],
        ],
    ],
];
```

Консоль
-------

Для обратки очереди используются консольные команды.

```bash
yii queue/listen
```

Команда `listen` запускает обработку очереди в режиме демона. Очередь опрашивается непрерывно.
Если добавляются новые задания, то они сразу же извлекаются и выполняются. Способ наиболее эфективен
если запускать команду через демон-супервизор, например `supervisord`.
