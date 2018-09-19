基本使用
============


配置
-------------

如果要使用这个扩展必须向下面这样配置它

```php
return [
    'bootstrap' => [
        'queue', // 把这个组件注册到控制台
    ],
    'components' => [
        'queue' => [
            'class' => \yii\queue\<driver>\Queue::class,
            'as log' => \yii\queue\LogBehavior::class,
            // 驱动的其他选项
        ],
    ],
];
```

可用的驱动程序列表及其配置文档在[README](README.md)目录中.

在代码中使用
-------------

每个被发送到队列的任务应该被定义为一个单独的类。
例如，如果您需要下载并保存一个文件，该类可能看起来如下:

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

下面是将任务添加到队列:

```php
Yii::$app->queue->push(new DownloadJob([
    'url' => 'http://example.com/image.jpg',
    'file' => '/tmp/image.jpg',
]));
```
将作业推送到队列中延时5分钟运行:

```php
Yii::$app->queue->delay(5 * 60)->push(new DownloadJob([
    'url' => 'http://example.com/image.jpg',
    'file' => '/tmp/image.jpg',
]));
```

**重要:** 只有一部分驱动支持延时运行。


处理队列
--------------

具体执行任务的方式取决于所使用的驱动程序。大多数驱动都可以使用
控制台命令，组件在您的应用程序中注册。有关更多细节，请参见相关驱动文档
。


作业状态
----------

该组件具有跟踪被推入队列的作业状态的能力。

```php
// 将作业推送到队列并获得其ID
$id = Yii::$app->queue->push(new SomeJob());

// 这个作业等待执行。
Yii::$app->queue->isWaiting($id);

// Worker 从队列获取作业，并执行它。
Yii::$app->queue->isReserved($id);

// Worker 作业执行完成。
Yii::$app->queue->isDone($id);
```

**Important:** RabbitMQ 驱动不支持作业状态。


消息传递的第三方员工Messaging third party workers
-----------------------------

您可以将任何数据传递给队列:

```php
Yii::$app->queue->push([
    'function' => 'download',
    'url' => 'http://example.com/image.jpg',
    'file' => '/tmp/image.jpg',
]);
```
如果使用的队列是第三方开发的，那么这是很有用的。

如果worker使用PHP以外的东西实现，那么您必须更改序列化数据的方式。例如,
JSON:

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

事件处理
---------------

队列可以触发以下事件:

| Event name                   | Event class | Triggered on                                              |
|------------------------------|-------------|-----------------------------------------------------------|
| Queue::EVENT_BEFORE_PUSH     | PushEvent   | Adding job to queue using `Queue::push()` method          |
| Queue::EVENT_AFTER_PUSH      | PushEvent   | Adding job to queue using `Queue::push()` method          |
| Queue::EVENT_BEFORE_EXEC     | ExecEvent   | Before each job execution                                 |
| Queue::EVENT_AFTER_EXEC      | ExecEvent   | After each success job execution                          |
| Queue::EVENT_AFTER_ERROR     | ExecEvent   | When uncaught exception occurred during the job execution |
| cli\Queue:EVENT_WORKER_START | WorkerEvent | When worker has been started                              |
| cli\Queue:EVENT_WORKER_LOOP  | WorkerEvent | Each iteration between requests to queue                  |
| cli\Queue:EVENT_WORKER_STOP  | WorkerEvent | When worker has been stopped                              |

您可以很容易地将自己的处理程序附加到这些事件中的任何一个。
例如，如果它的执行失败了，那么让我们延迟它:

```php
Yii::$app->queue->on(Queue::EVENT_AFTER_ERROR, function ($event) {
    if ($event->error instanceof TemporaryUnprocessableJobException) {
        $queue = $event->sender;
        $queue->delay(7200)->push($event->job);    
    }
});
```

事件日志
--------------

此组件提供了使用日志 `LogBehavior` 记录队列事件
[Yii built-in Logger](http://www.yiiframework.com/doc-2.0/guide-runtime-logging.html).

要使用它，只需按照以下方式配置队列组件:

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


多个队列
---------------

配置例子:

```php
return [
    'bootstrap' => [
        'queue1', // 第一个组件注册了自己的控制台命令
        'queue2', // 第二个组件注册了自己的控制台命令
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

使用例子:

```php
// 将任务发送到队列，通过标准工作人员进行处理
Yii::$app->queue1->push(new DownloadJob([
    'url' => 'http://example.com/image.jpg',
    'file' => '/tmp/image.jpg',
]));

// 将任务发送到另一个队列，由第三方工作人员处理
Yii::$app->queue2->push([
    'function' => 'download',
    'url' => 'http://example.com/image.jpg',
    'file' => '/tmp/image.jpg',
]);
```


限制
-----------

当使用队列时，务必记住，任务被放入队列中，并且在不同进程中从队列中获取。因此，如果您不确定在worker的作业环境中是否可用，则应在执行任务时避免外部依赖。

所有处理任务的数据都应该放到作业对象的属性中，并连同它一起发送到队列中。

如果您需要处理 `ActiveRecord` ，那么发送它的ID而不是对象本身。在处理时必须从DB提取它。

例如:

```php
Yii::$app->queue->push(new SomeJob([
    'userId' => Yii::$app->user->id,
    'bookId' => $book->id,
    'someUrl' => Url::to(['controller/action']),
]));
```

任务类:

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
