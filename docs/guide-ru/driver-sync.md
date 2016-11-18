Синхронный драйвер
==================

Этот драйвер выполняет задания синхронно, в том же сеансе. Драйвер может использоваться на этапе 
разработки и отладки приложения.

Настройка драйвера:

```php
return [
    'components' => [
        'queue' => [
            'class' => \zhuravljov\yii\queue\Queue::class,
            'driver' => \zhuravljov\yii\queue\sync\Driver::class,
        ],
    ],
];
```
