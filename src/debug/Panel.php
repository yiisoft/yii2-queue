<?php
/**
 * @link https://github.com/zhuravljov/yii2-queue
 * @copyright Copyright (c) 2017 Roman Zhuravlev
 * @license http://opensource.org/licenses/BSD-3-Clause
 */

namespace zhuravljov\yii\queue\debug;

use Yii;
use yii\base\ViewContextInterface;
use zhuravljov\yii\queue\JobEvent;
use zhuravljov\yii\queue\Queue;

/**
 * Class Panel
 *
 * @author Roman Zhuravlev <zhuravljov@gmail.com>
 */
class Panel extends \yii\debug\Panel implements ViewContextInterface
{
    private $_jobs = [];

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
        JobEvent::on(Queue::class, Queue::EVENT_AFTER_PUSH, function (JobEvent $event) {
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
    public function getViewPath()
    {
        return __DIR__ . '/views';
    }

    /**
     * @inheritdoc
     */
    public function getSummary()
    {
        return Yii::$app->view->render('summary', [
            'url' => $this->getUrl(),
            'count' => count($this->data['jobs']),
        ], $this);
    }

    /**
     * @inheritdoc
     */
    public function getDetail()
    {
        return Yii::$app->view->render('detail', [
            'jobs' => array_map(function ($serialized) {
                return unserialize($serialized);
            }, $this->data['jobs']),
        ], $this);
    }

    /**
     * @inheritdoc
     */
    public function save()
    {
        return ['jobs' => $this->_jobs];
    }
}