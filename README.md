## 基于YII2-Queue扩展

对Redis驱动进行扩展，支持子队列。

比如一个场景：

5个用户，第一个用户发布1000条任务到队列，后面的用户每人发布5条任务到队列。
正常的执行流程是将1000条执行完，再执行后面的用户的25条任务。在任务执行时长很长的情况下，影响了后面用户的操作体验，在此优化队列执行流程，分组执行，循环每组执行一条任务。

操作方式：

```php
Yii::$app->queue->group('group1')->push(new DownloadJob([
    'url' => 'http://example.com/image.jpg',
    'file' => '/tmp/image.jpg',
]));
```
