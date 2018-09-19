エラーと再試行可能ジョブ
========================

ジョブの実行は失敗する可能性があります。下手に書かれたコードによる内部的なエラーによって失敗する場合は、
まずもってコードを修正すべきです。
しかし、また、サービスやリソースが利用できないなど、外部的な問題によって
ジョブの実行が失敗する場合もあります。その場合は例外やタイムアウトになり得ます。

後者の場合においては、いくらか時間をおいて再試行できる方が良いでしょう。そうするための方法がいくつかあります。

> **Note:** 下記で説明する `ttr` 機能を使用するためには
> [PHP プロセス制御 (pcntl) 拡張](http://php.net/manual/ja/book.pcntl.php) がインストールされている必要があり、
> ワーカのコマンドが `--isolate` オプション (デフォルトで有効になっています) を使用する必要があります。

再試行のオプション
------------------

第一の方法はキュー・コンポーネントのオプションを使うものです。

```php
'components' => [
    'queue' => [
        'class' => \yii\queue\<driver>\Queue::class,
        'ttr' => 5 * 60, // ジョブ実行の最大時間
        'attempts' => 3, // 試行の最大回数
    ],
],
```

`ttr` (Time to reserve) オプションは、一つのジョブが成功裏に完了されるべき所要時間の最大の秒数を設定します。
ジョブが失敗するのには、次の二つの場合があります。

 1. `ttr` が経過する前にジョブが例外を投げる
 2. ジョブが完了するのに `ttr` よりも長く時間がかかる (タイムアウト)。
 そのためジョブの実行がワーカによって停止される。

どちらの場合も、ジョブは後で再試行するためにキューに戻されます。
ただし、注意してほしいのは、第一の場合において、ジョブが開始直後に停止したときでも、
`ttr` は同じように"消費"される、ということです。
つまり、`ttr` の残りの秒数が経過しなければジョブはキューに戻されません。

`attempts` オプションが試行の最大回数を設定します。
この数に達してもまだ完了しない場合、ジョブは終了したものとしてキューから削除されます。

これらのオプションはキューの中の全てのジョブに適用されます。この振る舞いを特定のジョブについて変更したい場合は、
次の方法を参照して下さい。

RetryableJobInterface
---------------------

再試行のロジックをもっと詳細に制御するために、ジョブは `RetryableJobInterface` を実装することが出来ます。
例えば、

```php
class SomeJob extends BaseObject implements RetryableJobInterface
{
    public function execute($queue)
    {
        //...
    }

    public function getTtr()
    {
        return 15 * 60;
    }

    public function canRetry($attempt, $error)
    {
        return ($attempt < 5) && ($error instanceof TemporaryException);
    }
}
```

`getTtr()` および `canRetry()` のメソッドは、上述したコンポーネントのオプションより高い優先度を持ちます。

イベント・ハンドラ
------------------

TTR と失敗したジョブの再試行回数を制御する第三の方法は、`Queue::EVENT_BEFORE_PUSH` と
`Queue::EVENT_AFTER_ERROR` のイベントを利用するものです。

TTR は `Queue::EVENT_BEFORE_PUSH` イベントによって設定することが出来ます。

```php
Yii::$app->queue->on(Queue::EVENT_BEFORE_PUSH, function (PushEvent $event) {
    if ($event->job instanceof SomeJob) {
        $event->ttr = 300;
    }
});
```

そして `Queue::EVENT_AFTER_ERROR` イベントを使って、もう一度試行するかどうかを決定することが出来ます。

```php
Yii::$app->queue->on(Queue::EVENT_AFTER_ERROR, function (ExecEvent $event) {
    if ($event->job instanceof SomeJob) {
        $event->retry = ($event->attempt < 5) && ($event->error instanceof TemporaryException);
    }
});
```

イベント・ハンドラは `RetryableJobInterface` のメソッドの後で実行され、従って、
最も高い優先度を持ちます。

制約
----

ジョブの再試行のフル・サポートが実装されているのは、[Beanstalk]、[データベース]、[ファイル]、[AMQP Interop] および [Redis] のドライバです。
[同期] ドライバは失敗したジョブを再試行しません。 [Gearman] ドライバは、再試行可能ジョブをサポートしていません。
[RabbitMQ] は基本的な再試行のみをサポートしており、試行回数は指定することが出来ません。

[Beanstalk]: driver-beanstalk.md
[データベース]: driver-db.md
[ファイル]: driver-file.md
[Redis]: driver-redis.md
[同期]: driver-sync.md
[Gearman]: driver-gearman.md
[RabbitMQ]: driver-amqp.md
[AMQP Interop]: driver-amqp-interop.md
