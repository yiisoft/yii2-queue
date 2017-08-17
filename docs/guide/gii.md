# Turn on Gii

In order to create a job template you can use the Gii code generator.

## Configuration

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

![default](https://user-images.githubusercontent.com/5769211/29277356-1317031c-811a-11e7-819b-6093c595cfa6.png)
