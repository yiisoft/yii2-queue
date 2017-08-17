# Включение Gii

Для создания заданий воркерам можно воспользоваться генератором кода.

## Настройка

Чтобы использовать раздел `job` в своем проекте, необходимо дополнить конфигурацию (например, `backend/config/main-local.php`) следующим образом:

```php
if (!YII_ENV_TEST) {
    $config['bootstrap'][] = 'gii';
    $config['modules']['gii'] = [
        'class' => 'yii\gii\Module',
        'generators' => [
            'job' => [
                'class' => yii\queue\gii\Generator::class,
            ],
        ],
    ];
}

```

После чего генератор кода для заданий будет добавлен в меню Gii и доступен по адресу `gii/job`

![default](https://user-images.githubusercontent.com/5769211/29277356-1317031c-811a-11e7-819b-6093c595cfa6.png)
