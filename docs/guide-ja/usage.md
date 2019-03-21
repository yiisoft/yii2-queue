基本的な使用方法
================


構成
----

このエクステンションを使うためには、以下のように構成しなければなりません。

```php
return [
    'bootstrap' => [
        'queue', // queue コンポーネントが自身のコンソール・コマンドを登録します
    ],
    'components' => [
        'queue' => [
            'class' => \yii\queue\<driver>\Queue::class,
            'as log' => \yii\queue\LogBehavior::class,
            // 他のドライバ・オプション
        ],
    ],
];
```

利用可能なドライバとそのドキュメントは [目次](README.md) に記載されています。


使用方法
--------

キューに送られるタスクはそれぞれ独立したクラスとして定義されなければなりません。
例えば、ファイルをダウンロードして保存すう必要がある場合、そのクラスは以下のようなものになります。

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

以下のようにして、キューにタスクを送ります。

```php
Yii::$app->queue->push(new DownloadJob([
    'url' => 'http://example.com/image.jpg',
    'file' => '/tmp/image.jpg',
]));
```
5 分後に走るべきジョブをキューにプッシュするには、

```php
Yii::$app->queue->delay(5 * 60)->push(new DownloadJob([
    'url' => 'http://example.com/image.jpg',
    'file' => '/tmp/image.jpg',
]));
```
**重要:** 全てのドライバが遅延実行をサポートしている訳ではありません。

ジョブをプッシュするときに優先度を指定することも出来ます。

```php
Yii::$app->queue->priority(10)->push(new ErrorNotification([
    'recipient' => 'notifyme@example.com',
]));
```

優先度の数値の低いジョブほど先に実行されます。優先度の既定値は `1024` です。

**重要:** 全てのドライバがジョブの優先度をサポートしている訳ではありません。


キューの処理
------------

タスクがどのように実行されるかは、正確には、使用されるドライバに依存します。
ほとんどのドライバでは、コンポーネントがアプリケーションに登録したコンソール・コマンドを使って実行されます。
詳細は、対応するドライバのドキュメントを参照して下さい。


ジョブの状態
------------

queue コンポーネントは、キューにプッシュされたジョブの状態を追跡することが出来ます。

```php
// ジョブをキューにプッシュして、メッセージ ID を取得する
$id = Yii::$app->queue->push(new SomeJob());

// ジョブが実行待ちであるかチェックする
Yii::$app->queue->isWaiting($id);

// ワーカがキューからジョブを受け取って実行しているかチェックする
Yii::$app->queue->isReserved($id);

// ワーカがジョブを完了したかチェックする
Yii::$app->queue->isDone($id);
```

**重要:** RabbitMQ ドライバはジョブの状態追跡をサポートしていません。


サード・パーティのワーカにメッセージを送る
------------------------------------------

キューにはどのようなデータでも渡すことが出来ます。

```php
Yii::$app->queue->push([
    'function' => 'download',
    'url' => 'http://example.com/image.jpg',
    'file' => '/tmp/image.jpg',
]);
```

このことは、キューが特別なサード・パーティのワーカを使って処理される場合に有用となります。

ワーカが PHP で実装されているのではない場合、データをシリアライズする方法を変更しなければなりません。
例えば、JSON にシリアライズする場合は、

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

イベントを処理する
------------------

キューは以下のイベントをトリガします。

| イベント名                   | イベント・クラス | トリガされるタイミング                               |
|------------------------------|-------------|-----------------------------------------------------------|
| Queue::EVENT_BEFORE_PUSH     | PushEvent   | `Queue::push()` メソッドを使ってジョブをキューに追加する前 |
| Queue::EVENT_AFTER_PUSH      | PushEvent   | `Queue::push()` メソッドを使ってジョブをキューに追加した後 |
| Queue::EVENT_BEFORE_EXEC     | ExecEvent   | ジョブを実行する前 |
| Queue::EVENT_AFTER_EXEC      | ExecEvent   | ジョブが完了した後 |
| Queue::EVENT_AFTER_ERROR     | ExecEvent   | ジョブ実行中にキャッチされない例外が発生したとき |
| cli\Queue:EVENT_WORKER_START | WorkerEvent | ワーカが開始されたとき |
| cli\Queue:EVENT_WORKER_LOOP  | WorkerEvent | キューに対するリクエストを繰り返すたびに |
| cli\Queue:EVENT_WORKER_STOP  | WorkerEvent | ワーカが停止されたとき |

これらのイベントの全てに対してあなた独自のハンドラをアタッチすることが簡単にできます。
例えば、ジョブが特殊な例外で失敗した場合に、時間をおいて再実行させてみましょう。

```php
Yii::$app->queue->on(Queue::EVENT_AFTER_ERROR, function ($event) {
    if ($event->error instanceof TemporaryUnprocessableJobException) {
        $queue = $event->sender;
        $queue->delay(7200)->push($event->job);
    }
});
```

イベントのロギング
------------------

queue コンポーネントは [Yii の内蔵ロガー](http://www.yiiframework.com/doc-2.0/guide-runtime-logging.html)
を使ってキューのイベントを記録する `LogBehavior` を提供しています。

これを有効にするためには、queue コンポーネントを以下のように構成するだけです。

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


複数のキュー
------------

構成例:

```php
return [
    'bootstrap' => [
        'queue1', // 第一のコンポーネントがそれ自身のコンソール・コマンドを登録
        'queue2', // 第二のコンポーネントがそれ自身のコンソール・コマンドを登録
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

使用例:

```php
// 標準のワーカで処理されるジョブをキューに送る
Yii::$app->queue1->push(new DownloadJob([
    'url' => 'http://example.com/image.jpg',
    'file' => '/tmp/image.jpg',
]));

// サード・パーティのワーカで処理されるタスクを別のキューに送る
Yii::$app->queue2->push([
    'function' => 'download',
    'url' => 'http://example.com/image.jpg',
    'file' => '/tmp/image.jpg',
]);
```


制限事項
--------

キューを使用する場合、キューに置かれたタスクは独立したプロセスによって取得される、ということを覚えておくことが重要です。
タスクを実行するときに外部的な依存は避けなければなりません。
なぜなら、ワーカがジョブを実行する環境においてその依存を利用できるとは限らないからです。

タスクを処理するために必要なデータは、ジョブ・オブジェクトのプロパティに設定して、ジョブと一緒にキューに送らなければなりません。

`ActiveRecord` を扱う必要がある場合は、オブジェクトそのものではなく、ID を送ります。
そして、処理するときに DB から読み出します。

例えば、

```php
Yii::$app->queue->push(new SomeJob([
    'userId' => Yii::$app->user->id,
    'bookId' => $book->id,
    'someUrl' => Url::to(['controller/action']),
]));
```

タスク・クラス:

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
