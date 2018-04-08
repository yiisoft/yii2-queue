同期ドライバ
============

`handle` プロパティが立っている場合に、同じプロセス内で同期的にタスクを実行します。
アプリケーションの開発とデバッグに使用することが出来ます。

構成例:

```php
return [
    'components' => [
        'queue' => [
            'class' => \yii\queue\sync\Queue::class,
            'handle' => false, // タスクを即時に実行するかどうか
        ],
    ],
];
```
