Syncronous Driver
=================

Runs tasks syncronously in the same process if `handle` property is turned on. Could be used when developing and debugging
application.

Configuration example:

```php
return [
    'components' => [
        'queue' => [
            'class' => \zhuravljov\yii\queue\drivers\sync\Queue::class,
            'handle' => false, // if tasks should be executed immediately
        ],
    ],
];
```
