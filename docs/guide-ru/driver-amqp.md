RabbitMQ драйвер
================

Драйвер работает с очередью на базе RabbitMQ.

В приложении должно быть установлено расширение `php-amqplib/php-amqplib`.

Пример настройки:

```php
return [
    'components' => [
        'queue' => [
            'class' => \zhuravljov\yii\queue\Queue::class,
            'driver' => [
                'class' => \zhuravljov\yii\queue\amqp\Driver::class,
                'host' => 'localhost',
                'port' => 5672,
                'user' => 'guest',
                'password' => 'guest',
                'queueName' => 'queue',
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
