Основы использования
====================

Настройка
---------

Чтобы использовать расширение в своем проекте, необходимо дополнить конфигурацию следующим образом:

```php
return [
    'bootstrap' => ['queue'],
    'components' => [
        'queue' => [
            'class' => \zhuravljov\yii\queue\Queue::class,
            'driver' => [], // Конфигурация драйвера
        ],
    ],
];
```

Список доступных драйверов и инструкции по их настройке можно посмотреть в [содержании](README.md).


Использование в коде
--------------------

Каждое задание, которое Вы планируете отправлять в очередь, оформляется в виде отдельного класса.
Например, если нужно скачать и сохранить файл, класс может выглядеть так:

```php
class DownloadJob extends Object implements \zhuravljov\yii\queue\Job
{
    public $url;
    public $file;
    
    public function run()
    {
        file_put_contents($this->file, file_get_contents($this->url));
    }
}
```

Отправить задание в очередь можно с помощью кода:

```php
Yii::$app->queue->push(new DownloadJob([
    'url' => 'http://example.com/image.jpg',
    'file' => '/tmp/image.jpg',
]));
```

Способы выполнения задач могут различаться, и зависят от используемого в расширении драйвера.


Сообщения для сторонних воркеров
--------------------------------

В очередь можно передавать гибридные данные, например:

```php
Yii::$app->queue->push([
    'function' => 'download',
    'url' => 'http://example.com/image.jpg',
    'file' => '/tmp/image.jpg',
]);
```

Это может быть полезно, если очередь обрабатывается сторонним воркером, разработанным специально.

Если воркер разрабатыватеся не на PHP, то, также, необходимо изменить способ сериализации данных,
чтобы сообщения кодировались доступным воркеру форматом. Например, настроить сериализацию в json,
самым простым способом, можно так:

```php
return [
    'components' => [
        'queue' => [
            'class' => \zhuravljov\yii\queue\Queue::class,
            'driver' => [
                'serializer' => 'json_encode',
            ],
        ],
    ],
];
```

Несколько очередей
------------------

Пример настройки:

```php
return [
    'components' => [
        'queue1' => [
            'class' => \zhuravljov\yii\queue\Queue::class,
            'driver' => \zhuravljov\yii\queue\redis\Driver::class,
        ],
        'queue2' => [
            'class' => \zhuravljov\yii\queue\Queue::class,
            'driver' => [
                'class' => \zhuravljov\yii\queue\db\Driver::class,
                'serializer' => 'json_encode',
            ],
        ],
    ],
];
```

Пример использования:

```php
// Отправка задания в очередь для обработки встроенным воркером
Yii::$app->queue->push(new DownloadJob([
    'url' => 'http://example.com/image.jpg',
    'file' => '/tmp/image.jpg',
]));

// Отправка сообщения в другую очередь для обработки сторонним воркером
Yii::$app->queue2->push([
    'function' => 'download',
    'url' => 'http://example.com/image.jpg',
    'file' => '/tmp/image.jpg',
]);
```
