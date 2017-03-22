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
            'as log' => \zhuravljov\yii\queue\LogBehavior::class,
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

Отправить задание для выполнения с задержкой в 5 минут:

```php
Yii::$app->queue->later(new DownloadJob([
    'url' => 'http://example.com/image.jpg',
    'file' => '/tmp/image.jpg',
]), 5 * 60);
```

**Внимание:** не все драйверы поддерживают отложенное выполнение заданий.


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

Это допустимо, если очередь обрабатывается сторонним воркером, разработанным специально.

Если воркер разрабатыватеся не на PHP, то, также, необходимо изменить способ сериализации данных,
чтобы сообщения кодировались доступным воркеру форматом. Например, настроить сериализацию в json,
самым простым способом, можно так:

```php
return [
    'components' => [
        'queue' => [
            'class' => \zhuravljov\yii\queue\Queue::class,
            'driver' => [
                'serializer' => \zhuravljov\yii\queue\serializers\JsonSerializer::class,
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
                'serializer' => \zhuravljov\yii\queue\serializers\JsonSerializer::class,
            ],
        ],
    ],
];
```

Пример использования:

```php
// Отправка задания в очередь для обработки встроенным воркером
Yii::$app->queue1->push(new DownloadJob([
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


Ограничения
-----------

Используя очереди важно помнить, что задачи ставятся в очередь и извлекаются из нее в разных
процессах. Поэтому, при обработке задания, избегайте использования внешних зависимостей, когда
не уверены в том, что они будут доступны в том окружении, где работает воркер.

Все данные, необходимые для выполнения задания, нужно оформлять в виде свойств Вашего job-объекта, и
отправлять в очередь вместе с ним.

Если в задании нужно работать с моделью `ActiveRecord`, вместо самой модели передавайте ее ID. А в
момент выполнения извлекайте ее из базы данных.

Например:

```php
Yii::$app->queue->push(new SomeJob([
    'userId' => Yii::$app->user->id,
    'bookId' => $book->id,
    'someUrl' => Url::to(['controller/action']),
]));
```

Класс задания:

```php
class SomeJob extends Object implements \zhuravljov\yii\queue\Job
{
    public $userId;
    public $bookId;
    public $someUrl;
    
    public function run()
    {
        $user = User::findOne($this->userId);
        $book = Book::findOne($this->bookId);
        //...
    }
}
```
