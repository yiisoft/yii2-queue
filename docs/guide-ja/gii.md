Gii コード・ジェネレータ
========================

ジョブのテンプレートを生成するために Gii コード・ジェネレータを使用することが出来ます。

構成
----

Gii ジョブ・ジェネレータを使うためには、以下のように構成しなければなりません。

```php
if (!YII_ENV_TEST) {
    $config['bootstrap'][] = 'gii';
    $config['modules']['gii'] = [
        'class' => 'yii\gii\Module',
        'generators' => [
            'job' => [
                'class' => \yii\queue\gii\Generator::class,
            ],
        ],
    ];
}

```

このようにすると、Gii のメニューにジョブ・ジェネレータが表示されるようになります。

![default](https://user-images.githubusercontent.com/1656851/29426628-e9a3e5ae-838f-11e7-859f-6f3cb8649f02.png)
