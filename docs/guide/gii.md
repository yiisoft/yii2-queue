Code generator Gii
==================

In order to create a job template you can use the Gii code generator.

Configuration
-------------

In order to use Gii job generator you have to configure it like the following:  
(for example into `backend/config/main-local.php`)

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

After doing it you'll find the generator in the menu.

![default](https://user-images.githubusercontent.com/1656851/29426628-e9a3e5ae-838f-11e7-859f-6f3cb8649f02.png)
