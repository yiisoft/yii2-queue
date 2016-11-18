Отладка
=======

Для удобств разработки в отладочный модуль Yii2 можно добавить панель, которая будет выводить список
поставленных в очередь заданий и их количество.

Отладочное расширение `yiisoft/yii2-debug` должно быть установлено в Вашем приложении.

Настройка приложения:

```php
return [
    'bootstrap' => ['debug', 'queue'],
    'modules' => [
        'debug' => [
            'class' => \yii\debug\Module::class,
            'panels' => [
                'queue' => \zhuravljov\yii\queue\debug\Panel::class,
            ],
        ],
    ],
];
```
