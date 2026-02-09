<?php

declare(strict_types=1);

/**
 * @var View $this
 * @var ActiveForm $form
 * @var Generator $generator
 */

use yii\queue\gii\Generator;
use yii\web\View;
use yii\widgets\ActiveForm;

?>
<?= $form->field($generator, 'jobClass')->textInput(['autofocus' => true]) ?>
<?= $form->field($generator, 'properties') ?>
<?= $form->field($generator, 'retryable')->checkbox() ?>
<?= $form->field($generator, 'ns') ?>
<?= $form->field($generator, 'baseClass') ?>
