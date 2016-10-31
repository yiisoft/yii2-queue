<?php

namespace zhuravljov\yii\queue;

use Yii;
use yii\debug\Panel;
use yii\helpers\VarDumper;

/**
 * Class Panel
 *
 * @author Roman Zhuravlev <zhuravljov@gmail.com>
 */
class DebugPanel extends Panel
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
        $url = $this->getUrl();
        $count = count($this->data['jobs']);

        return <<<HTML
<div class="yii-debug-toolbar__block">
    <a href="{$url}">Queue <span class="yii-debug-toolbar__label">{$count}</span></a>
</div>
HTML;
    }

    /**
     * @inheritdoc
     */
    public function getDetail()
    {
        $count = count($this->data['jobs']);
        $jobs = [];
        foreach ($this->data['jobs'] as $serialized) {
            $jobs[] = unserialize($serialized);
        }
        $html = VarDumper::dumpAsString($jobs, 10, true);

        return <<<HTML
<h1>Pushed {$count} jobs</h1>
{$html}
HTML;
    }

    /**
     * @inheritdoc
     */
    public function save()
    {
        return ['jobs' => $this->_jobs];
    }
}