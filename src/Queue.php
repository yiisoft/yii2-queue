<?php

namespace zhuravljov\yii\queue;

use Yii;
use yii\base\BootstrapInterface;
use yii\base\Component;

/**
 * Class Queue
 *
 * @property string $id of component
 *
 * @author Roman Zhuravlev <zhuravljov@gmail.com>
 */
class Queue extends Component implements BootstrapInterface
{
    /**
     * @event JobEvent
     */
    const EVENT_AFTER_PUSH = 'afterPush';
    /**
     * @event JobEvent
     */
    const EVENT_BEFORE_WORK = 'beforeWork';
    /**
     * @event JobEvent
     */
    const EVENT_AFTER_WORK = 'afterWork';
    /**
     * @event ErrorEvent
     */
    const EVENT_AFTER_ERROR = 'afterError';

    /**
     * @var Driver|array|string
     */
    public $driver = [];

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
        $this->driver = Yii::createObject($this->driver, [$this]);
    }

    /**
     * @inheritdoc
     */
    public function bootstrap($app)
    {
        if ($this->driver instanceof BootstrapInterface) {
            $this->driver->bootstrap($app);
        }
    }

    /**
     * @return string component id
     */
    public function getId()
    {
        foreach (Yii::$app->getComponents(false) as $id => $component) {
            if ($component === $this) {
                return $id;
            }
        }
        return null;
    }

    /**
     * @param Job|mixed $job
     */
    public function push($job)
    {
        $this->driver->push($job);
        $this->trigger(self::EVENT_AFTER_PUSH, new JobEvent(['job' => $job]));
    }

    /**
     * @param Job $job
     * @return boolean
     */
    public function run(Job $job)
    {
        $error = null;
        $this->trigger(self::EVENT_BEFORE_WORK, new JobEvent(['job' => $job]));
        try {
            $job->run();
        } catch (\Exception $error) {
            $this->trigger(self::EVENT_AFTER_ERROR, new ErrorEvent(['job' => $job, 'error' => $error]));
        }
        if (!$error) {
            $this->trigger(self::EVENT_AFTER_WORK, new JobEvent(['job' => $job]));
        }

        return !$error;
    }
}