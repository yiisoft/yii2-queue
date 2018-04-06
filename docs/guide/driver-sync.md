Synchronous Driver
==================

Runs tasks synchronously in the same process if the `handle` property is turned on.
It could be used when developing and debugging an application.

Configuration example:

```php
return [
    'components' => [
        'queue' => [
            'class' => \yii\queue\sync\Queue::class,
            'handle' => false, // whether tasks should be executed immediately
        ],
    ],
];
```
