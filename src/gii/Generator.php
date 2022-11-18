<?php
/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

namespace yii\queue\gii;

use Yii;
use yii\base\BaseObject;
use yii\gii\CodeFile;
use yii\queue\JobInterface;
use yii\queue\RetryableJobInterface;

/**
 * This generator will generate a job.
 *
 * @author Roman Zhuravlev <zhuravljov@gmail.com>
 */
class Generator extends \yii\gii\Generator
{
    public $jobClass;
    public $properties;
    public $retryable = false;
    public $ns = 'app\jobs';
    public $baseClass = BaseObject::class;


    /**
     * @inheritdoc
     */
    public function getName()
    {
        return 'Job Generator';
    }

    /**
     * @inheritdoc
     */
    public function getDescription()
    {
        return 'This generator generates a Job class for the queue.';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return array_merge(parent::rules(), [
            [['jobClass', 'properties', 'ns', 'baseClass'], 'trim'],
            [['jobClass', 'ns', 'baseClass'], 'required'],
            ['jobClass', 'match', 'pattern' => '/^\w+$/', 'message' => 'Only word characters are allowed.'],
            ['jobClass', 'validateJobClass'],
            ['properties', 'match', 'pattern' => '/^[a-z_][a-z0-9_,\\s]*$/i', 'message' => 'Must be valid class properties.'],
            ['retryable', 'boolean'],
            ['ns', 'validateNamespace'],
            ['baseClass', 'validateClass'],
        ]);
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return array_merge(parent::attributeLabels(), [
            'jobClass' => 'Job Class',
            'properties' => 'Job Properties',
            'retryable' => 'Retryable Job',
            'ns' => 'Namespace',
            'baseClass' => 'Base Class',
        ]);
    }

    /**
     * @inheritdoc
     */
    public function hints()
    {
        return array_merge(parent::hints(), [
            'jobClass' => 'This is the name of the Job class to be generated, e.g., <code>SomeJob</code>.',
            'properties' => 'Job object property names. Separate multiple properties with commas or spaces, e.g., <code>prop1, prop2</code>.',
            'retryable' => 'Job object will implement <code>RetryableJobInterface</code> interface.',
            'ns' => 'This is the namespace of the Job class to be generated.',
            'baseClass' => 'This is the class that the new Job class will extend from.',
        ]);
    }

    /**
     * @inheritdoc
     */
    public function stickyAttributes()
    {
        return array_merge(parent::stickyAttributes(), ['ns', 'baseClass']);
    }

    /**
     * @inheritdoc
     */
    public function requiredTemplates()
    {
        return ['job.php'];
    }

    /**
     * @inheritdoc
     */
    public function generate()
    {
        $params = [];
        $params['jobClass'] = $this->jobClass;
        $params['ns'] = $this->ns;
        $params['baseClass'] = '\\' . ltrim($this->baseClass, '\\');
        $params['interfaces'] = [];
        if (!$this->retryable) {
            if (!is_a($this->baseClass, JobInterface::class, true)) {
                $params['interfaces'][] = '\\' . JobInterface::class;
            }
        } else {
            if (!is_a($this->baseClass, RetryableJobInterface::class, true)) {
                $params['interfaces'][] = '\\' . RetryableJobInterface::class;
            }
        }
        $params['properties'] = array_unique(preg_split('/[\s,]+/', $this->properties, -1, PREG_SPLIT_NO_EMPTY));

        $jobFile = new CodeFile(
            Yii::getAlias('@' . str_replace('\\', '/', $this->ns)) . '/' . $this->jobClass . '.php',
            $this->render('job.php', $params)
        );

        return [$jobFile];
    }

    /**
     * Validates the job class.
     *
     * @param string $attribute job attribute name.
     */
    public function validateJobClass($attribute)
    {
        if ($this->isReservedKeyword($this->$attribute)) {
            $this->addError($attribute, 'Class name cannot be a reserved PHP keyword.');
        }
    }

    /**
     * Validates the namespace.
     *
     * @param string $attribute Namespace attribute name.
     */
    public function validateNamespace($attribute)
    {
        $value = $this->$attribute;
        $value = ltrim($value, '\\');
        $path = Yii::getAlias('@' . str_replace('\\', '/', $value), false);
        if ($path === false) {
            $this->addError($attribute, 'Namespace must be associated with an existing directory.');
        }
    }
}
