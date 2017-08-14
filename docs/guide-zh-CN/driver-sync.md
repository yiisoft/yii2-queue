Syncronous 驱动
=================

如果打开 `handle`属性，则在使用过程中同步运行任务。
一般在开发和调试应用程序时使用。

配置示例:

```php
return [
    'components' => [
        'queue' => [
            'class' => \yii\queue\sync\Queue::class,
            'handle' => false, // 任务是否立即执行
        ],
    ],
];
```
