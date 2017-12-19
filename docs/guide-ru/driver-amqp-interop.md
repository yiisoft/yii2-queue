AMQP Interop драйвер
====================

Драйвер работает с очередью RabbitMQ.

Чтобы он работал в проект нужно добавить [amqp interop] транспорт, например пакет [enqueue/amqp-lib].

Возможности:

* Транспорты совместимые с [amqp interop]:
  * [enqueue/amqp-ext] на основе [PHP amqp extension]
  * [enqueue/amqp-lib] на основе [php-amqplib/php-amqplib]
  * [enqueue/amqp-bunny] на основе [bunny]
* Приоритеты заданий
* Отложенное выполнение
* Поддерживается TTR
* Повторное выполнение после неудачных попыток
* Опции: `vhost`, `connection_timeout`, `qos_prefetch_count` и т.д.
* Защищенное подключение к брокеру (SSL)
* DSN: `amqp:`, `amqps:` или `amqp://user:pass@localhost:1000/vhost`

[amqp interop]: https://github.com/queue-interop/queue-interop#amqp-interop
[enqueue/amqp-ext]: https://github.com/php-enqueue/amqp-ext
[PHP amqp extension]: https://github.com/pdezwart/php-amqp
[enqueue/amqp-lib]: https://github.com/php-enqueue/amqp-lib
[php-amqplib/php-amqplib]: https://github.com/php-amqplib/php-amqplib
[enqueue/amqp-bunny]: https://github.com/php-enqueue/amqp-bunny
[bunny]: https://github.com/jakubkulhan/bunny

Пример конфига:

```php
return [
    'bootstrap' => [
        'queue', // Компонент регистрирует свои консольные команды
    ],
    'components' => [
        'queue' => [
            'class' => \yii\queue\amqp_interop\Queue::class,
            'port' => 5672,
            'user' => 'guest',
            'password' => 'guest',
            'queueName' => 'queue',
            'driver' => yii\queue\amqp_interop\Queue::ENQUEUE_AMQP_LIB,
            // или
            'dsn' => 'amqp://guest:guest@localhost:5672/%2F',
            // или
            'dsn' => 'amqp:',
        ],
    ],
];
```

Консоль
-------

Для обработки очереди используются консольные команды.

```sh
yii queue/listen
```

Команда `listen` запускает обработку очереди в режиме демона. Очередь опрашивается непрерывно.
Если добавляются новые задания, то они сразу же извлекаются и выполняются. Способ наиболее эфективен
если запускать команду через [supervisor](worker.md#supervisor) или [systemd](worker.md#systemd).

Для команды `listen` доступны следующие опции:

- `--verbose`, `-v`: состояние обработки заданий выводится в консоль.
- `--isolate`: каждое задание выполняется в отдельном дочернем процессе.
- `--color`: подсветка вывода в режиме `--verbose`.
