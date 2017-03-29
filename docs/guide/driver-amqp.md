RabbitMQ Driver
===============

The driver works with RabbitMQ queues.

In order for it to work you should add `php-amqplib/php-amqplib` package to your project.

Configuration example:

```php
return [
    'bootstrap' => ['queue'],
    'components' => [
        'queue' => [
            'class' => \zhuravljov\yii\queue\amqp\Queue::class,
            'host' => 'localhost',
            'port' => 5672,
            'user' => 'guest',
            'password' => 'guest',
            'queueName' => 'queue',
        ],
    ],
];
```

Console
-------

Console is used to listen and process queued tasks.

```bash
yii queue/listen
```

`listen` command launches a daemon which infinitely queries the queue. If there are new tasks they're immediately
obtained and executed. This method is most effificient when command is properly daemonized via supervisor such as
`supervisord`.
