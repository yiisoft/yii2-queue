デバッグ
========

開発中は、Yii2 デバッグ・モジュールのためのパネルを追加したいと思うことも有るでしょう。
このパネルは、キューにあるタスクのカウンタとリストを表示します。

このパネルが表示されるためには、`yiisoft/yii2-debug` モジュールがアプリケーションにインストールされていなければなりません。

以下のようにアプリケーションを構成して下さい。

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
