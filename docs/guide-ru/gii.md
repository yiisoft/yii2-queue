Генератор кода Gii
==================

Для создания заданий воркерам можно воспользоваться генератором кода.

Настройка
---------

Чтобы использовать раздел `job` в своем проекте, необходимо дополнить конфигурацию (например, `backend/config/main-local.php`) следующим образом:

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

После чего генератор кода для заданий будет добавлен в меню.

![default](https://user-images.githubusercontent.com/1656851/29426628-e9a3e5ae-838f-11e7-859f-6f3cb8649f02.png)
