调试
=========

为了使开发过程更加友好，您可以向Yii2调试模块添加一个面板。面板显示
计数器和队列任务列表。

`yiisoft/yii2-debug` 应该安装在你的应用程序中，以便在调试面板中显示

将您的应用程序配置如下：

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
