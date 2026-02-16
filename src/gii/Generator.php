<?php

/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

declare(strict_types=1);

namespace yii\queue\gii;

use Yii;
use yii\base\BaseObject;
use yii\gii\CodeFile;
use yii\gii\Generator as BaseGenerator;
use yii\queue\JobInterface;
use yii\queue\RetryableJobInterface;

/**
 * This generator will generate a job.
 *
 * @author Roman Zhuravlev <zhuravljov@gmail.com>
 */
class Generator extends BaseGenerator
{
    public string $jobClass = '';
    public string $properties = '';
    public bool $retryable = false;
    public string $ns = 'app\jobs';
    public string $baseClass = BaseObject::class;

    /**
     * @inheritdoc
     */
    public function getName(): string
    {
        return 'Job Generator';
    }

    /**
     * @inheritdoc
     */
    public function getDescription(): string
    {
        return 'This generator generates a Job class for the queue.';
    }

    /**
     * @inheritdoc
     */
    public function rules(): array
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
    public function attributeLabels(): array
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
    public function hints(): array
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
    public function stickyAttributes(): array
    {
        return array_merge(parent::stickyAttributes(), ['ns', 'baseClass']);
    }

    /**
     * @inheritdoc
     */
    public function requiredTemplates(): array
    {
        return ['job.php'];
    }

    /**
     * @inheritdoc
     */
    public function generate(): array
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

        $alias = Yii::getAlias('@' . str_replace('\\', '/', $this->ns));
        if (false === $alias) {
            return [];
        }
        $jobFile = new CodeFile(
            $alias . '/' . $this->jobClass . '.php',
            $this->render('job.php', $params)
        );

        return [$jobFile];
    }

    /**
     * Validates the job class.
     *
     * @param string $attribute job attribute name.
     */
    public function validateJobClass(string $attribute): void
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
    public function validateNamespace(string $attribute): void
    {
        /** @var string $value */
        $value = $this->$attribute;
        $value = ltrim($value, '\\');
        $path = Yii::getAlias('@' . str_replace('\\', '/', $value), false);
        if ($path === false) {
            $this->addError($attribute, 'Namespace must be associated with an existing directory.');
        }
    }
}
