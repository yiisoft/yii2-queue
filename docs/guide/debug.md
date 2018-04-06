Debugging
=========

During development you may want to add a panel for the Yii2 debug module.
The panel displays a counter and a list of queued tasks.

The `yiisoft/yii2-debug` module should be installed in your application for the panel to be displayed.

Configure your application like the following:

```php
return [
    'modules' => [
        'debug' => [
            'class' => \yii\debug\Module::class,
            'panels' => [
                'queue' => \yii\queue\debug\Panel::class,
            ],
        ],
    ],
];
```
