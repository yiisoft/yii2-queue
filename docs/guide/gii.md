# Turn on Gii

For create job template you can be use the Gii code generator.

## Configuration

In order to use gii/job you have to configure it like the following:  
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

This will be adding Job Generator `gii/job` to the menu and turn on it.

![default](https://user-images.githubusercontent.com/5769211/29277356-1317031c-811a-11e7-819b-6093c595cfa6.png)
