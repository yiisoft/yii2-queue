Debugging
=========

In order for development process to be more friendly you may add a panel to Yii2 debug module. The panel displays a
counter and a list of queued tasks.

`yiisoft/yii2-debug` should be installed in your application in order for the panel to be displayed.

Configure your application like the following:

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
