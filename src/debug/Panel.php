<?php

namespace zhuravljov\yii\queue\debug;

use Yii;
use zhuravljov\yii\queue\Event;
use zhuravljov\yii\queue\Queue;

/**
 * Class Panel
 *
 * @author Roman Zhuravlev <zhuravljov@gmail.com>
 */
class Panel extends \yii\debug\Panel
{
    private $_jobs = [];

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
        Event::on(Queue::class, Queue::EVENT_ON_PUSH, function (Event $event) {
            $this->_jobs[] = serialize($event->job);
        });
    }

    /**
     * @inheritdoc
     */
    public function getName()
    {
        return 'Queue';
    }

    /**
     * @inheritdoc
     */
    public function getSummary()
    {
        return Yii::$app->view->render(
            '@vendor/zhuravljov/yii2-queue/src/debug/views/summary',
            ['panel' => $this, 'count' => count($this->data['jobs'])]
        );
    }

    /**
     * @inheritdoc
     */
    public function getDetail()
    {
        $jobs = [];
        foreach ($this->data['jobs'] as $serialized) {
            $jobs[] = unserialize($serialized);
        }
        return Yii::$app->view->render(
            '@vendor/zhuravljov/yii2-queue/src/debug/views/detail',
            ['panel' => $this, 'jobs' => $jobs]
        );
    }

    /**
     * @inheritdoc
     */
    public function save()
    {
        return ['jobs' => $this->_jobs];
    }
}