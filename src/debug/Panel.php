<?php

declare(strict_types=1);

/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

namespace yii\queue\debug;

use Exception;
use Yii;
use yii\base\NotSupportedException;
use yii\base\ViewContextInterface;
use yii\debug\Panel as BasePanel;
use yii\helpers\VarDumper;
use yii\queue\JobInterface;
use yii\queue\PushEvent;
use yii\queue\Queue;

/**
 * Debug Panel.
 *
 * @author Roman Zhuravlev <zhuravljov@gmail.com>
 * @psalm-suppress PropertyNotSetInConstructor
 */
class Panel extends BasePanel implements ViewContextInterface
{
    private array $jobs = [];

    /**
     * @inheritdoc
     */
    public function getName(): string
    {
        return 'Queue';
    }

    /**
     * @inheritdoc
     */
    public function init(): void
    {
        parent::init();
        PushEvent::on(Queue::class, Queue::EVENT_AFTER_PUSH, function (PushEvent $event) {
            $this->jobs[] = $this->getPushData($event);
        });
    }

    /**
     * @param PushEvent $event
     * @return array
     */
    protected function getPushData(PushEvent $event): array
    {
        $data = [];
        foreach (Yii::$app->getComponents(false) as $id => $component) {
            if ($component === $event->sender) {
                $data['sender'] = $id;
                break;
            }
        }
        $data['id'] = $event->id;
        $data['ttr'] = $event->ttr;
        $data['delay'] = $event->delay;
        $data['priority'] = $event->priority;
        if ($event->job instanceof JobInterface) {
            $data['class'] = get_class($event->job);
            $data['properties'] = [];
            foreach (get_object_vars($event->job) as $property => $value) {
                $data['properties'][$property] = VarDumper::dumpAsString($value);
            }
        } else {
            $data['data'] = VarDumper::dumpAsString($event->job);
        }

        return $data;
    }

    /**
     * @inheritdoc
     */
    public function save()
    {
        return ['jobs' => $this->jobs];
    }

    /**
     * @inheritdoc
     */
    public function getViewPath(): string
    {
        return __DIR__ . '/views';
    }

    /**
     * @inheritdoc
     */
    public function getSummary(): string
    {
        /** @psalm-var array{jobs: array} $this->data */
        return Yii::$app->view->render('summary', [
            'url' => $this->getUrl(),
            'count' => isset($this->data['jobs']) ? count($this->data['jobs']) : 0,
        ], $this);
    }

    /**
     * @inheritdoc
     */
    public function getDetail(): string
    {
        /** @psalm-var array{jobs: array} $this->data */
        $jobs = $this->data['jobs'] ?? [];
        foreach ($jobs as &$job) {
            /** @psalm-var array{sender: string, id: string|int} $job */
            $job['status'] = 'unknown';
            /** @var Queue $queue */
            if ($queue = Yii::$app->get($job['sender'], false)) {
                try {
                    /** @psalm-var Queue $queue */
                    if ($queue->isWaiting($job['id'])) {
                        $job['status'] = 'waiting';
                    } elseif ($queue->isReserved($job['id'])) {
                        $job['status'] = 'reserved';
                    } elseif ($queue->isDone($job['id'])) {
                        $job['status'] = 'done';
                    }
                } catch (NotSupportedException|Exception $e) {
                    $job['status'] = $e->getMessage();
                }
            }
        }
        unset($job);

        return Yii::$app->view->render('detail', compact('jobs'), $this);
    }
}
