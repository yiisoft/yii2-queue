Основы использования
====================


Настройка
---------

Чтобы использовать расширение в своем проекте, необходимо дополнить конфигурацию следующим образом:

```php
return [
    'bootstrap' => [
        'queue', // Компонент регистрирует свои консольные команды 
    ],
    'components' => [
        'queue' => [
            'class' => \yii\queue\<driver>\Queue::class,
            'as log' => \yii\queue\LogBehavior::class,
            // Индивидуальные настройки драйвера
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
class DownloadJob extends BaseObject implements \yii\queue\JobInterface
{
    public $url;
    public $file;
    
    public function execute($queue)
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

Отправить задание для выполнения с задержкой в 5 минут:

```php
Yii::$app->queue->delay(5 * 60)->push(new DownloadJob([
    'url' => 'http://example.com/image.jpg',
    'file' => '/tmp/image.jpg',
]));
```

**Внимание:** Драйвера RabbitMQ и Gearman не поддерживают отложенные задания.


Обработка очереди
-----------------

Способы обработки очереди задач могут различаться, и зависят от используемого драйвера. В большей
части драйверов воркеры запускаются с помощью консольных команд, которые компонент сам регистрирует
в приложении. Детальное описание смотрите в документации конкретного драйвера.


Статус заданий
--------------

Компонент дает возможность отслеживать состояние поставленных в очередь заданий.

```php
// Отправляем задание в очередь, и получаем его ID.
$id = Yii::$app->queue->push(new SomeJob());

// Задание еще находится в очереди.
Yii::$app->queue->isWaiting($id);

// Воркер взял задание из очереди, и выполняет его.
Yii::$app->queue->isReserved($id);

// Воркер уже выполнил задание.
Yii::$app->queue->isDone($id);
```

**Внимание:** Драйвера RabbitMQ и AWS SQS не поддерживает статусы.


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

Это допустимо, если очередь обрабатывается сторонним воркером, разработанным индивидуально.

Если воркер разрабатывается не на PHP, то, также, необходимо изменить способ сериализации данных,
чтобы сообщения кодировались доступным воркеру форматом. Например, настроить сериализацию в json,
самым простым способом, можно так:

```php
return [
    'components' => [
        'queue' => [
            'class' => \yii\queue\<driver>\Queue::class,
            'strictJobType' => false,
            'serializer' => \yii\queue\serializers\JsonSerializer::class,
        ],
    ],
];
```

Обработка событий
-----------------

Очередь вызывает следующие события:

| Событие                      | Класс события | Когда вызывается                                              |
|------------------------------|---------------|---------------------------------------------------------------|
| Queue::EVENT_BEFORE_PUSH     | PushEvent     | Добавление задания в очередь используя метод `Queue::push()`  |
| Queue::EVENT_AFTER_PUSH      | PushEvent     | Добавление задания в очередь используя метод `Queue::push()`  |
| Queue::EVENT_BEFORE_EXEC     | ExecEvent     | Перед каждым выполнением задания                              |
| Queue::EVENT_AFTER_EXEC      | ExecEvent     | После каждого успешного выполнения задания                    |
| Queue::EVENT_AFTER_ERROR     | ExecEvent     | Если при выполнение задания случилось непойманное исключение  |
| cli\Queue:EVENT_WORKER_START | WorkerEvent   | В момент запуска нового воркера                               |
| cli\Queue:EVENT_WORKER_LOOP  | WorkerEvent   | В цикле между опросами очереди                                |
| cli\Queue:EVENT_WORKER_STOP  | WorkerEvent   | В момент остановки воркера                                    |

Вы с лёгкостью можете подключить свой собственный слушатель на любое из этих событий.
Например, давайте отложим выполнение задания, которое выбросило специальное исключение:

```php
Yii::$app->queue->on(Queue::EVENT_AFTER_ERROR, function ($event) {
    if ($event->error instanceof TemporaryUnprocessableJobException) {
        $queue = $event->sender;
        $queue->delay(7200)->push($event->job);    
    }
});
```

Логирование событий
-------------------

Этот компонент предоставляет `LogBehavior` для логирования событий, используя
[встроенный в Yii логгер](http://www.yiiframework.com/doc-2.0/guide-runtime-logging.html). 

Чтобы использовать его, просто подключите это поведение в конфигурации компонента, как показано в
примере:

```php
return [
    'components' => [
        'queue' => [
            'class' => \yii\queue\redis\Queue::class,
            'as log' => \yii\queue\LogBehavior::class
        ],
    ],
];
```

Несколько очередей
------------------

Пример настройки:

```php
return [
    'bootstrap' => [
        'queue1', // Первый компонент регистрирует свои консольные команды 
        'queue2', // Второй - свои 
    ],
    'components' => [
        'queue1' => [
            'class' => \yii\queue\redis\Queue::class,
        ],
        'queue2' => [
            'class' => \yii\queue\db\Queue::class,
            'strictJobType' => false,
            'serializer' => \yii\queue\serializers\JsonSerializer::class,
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

Используя очереди важно помнить, что задачи ставятся и извлекаются из очереди в разных процессах.
Поэтому, при обработке задания, избегайте использования внешних зависимостей, когда не уверены
в том, что они будут доступны в окружении, где работает воркер.

Все данные, необходимые для выполнения задания, нужно оформлять в виде свойств Вашего job-объекта,
и отправлять в очередь вместе с ним.

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
class SomeJob extends BaseObject implements \yii\queue\JobInterface
{
    public $userId;
    public $bookId;
    public $someUrl;
    
    public function execute($queue)
    {
        $user = User::findOne($this->userId);
        $book = Book::findOne($this->bookId);
        //...
    }
}
```
