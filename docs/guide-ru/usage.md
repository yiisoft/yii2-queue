Основы использования
====================

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
А список доступных в данный момент драйверов можно посмотреть в [содержании](README.md).
