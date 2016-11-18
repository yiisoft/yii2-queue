Установка
=========

Установка composer-пакета
-------------------------

Предпочтительнее установить это расширение через [composer](http://getcomposer.org/download/).

Либо запустите

```
php composer.phar require --dev --prefer-dist zhuravljov/yii2-queue
```

либо добавьте

```
"zhuravljov/yii2-queue": "*"
```

в `require` секцию Вашего файла `composer.json`.

Конфигурация приложения
-----------------------

После того, как расширение было установлено, необходимо дополнить конфигурацию вашего приложения
следующим образом:

```php
return [
    'bootstrap' => ['queue'],
    'components' => [
        'queue' => [
            'class' => \zhuravljov\yii\queue\Queue::class,
            'driver' => [], // Конфигурация драйвера
        ],
    ],
];
```

Список доступных драйверов и инструкции по их настройке можно посмотреть в [содержании](README.md).
