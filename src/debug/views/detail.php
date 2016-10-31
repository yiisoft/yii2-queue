<?php
/**
 * @var \zhuravljov\yii\queue\debug\Panel $panel
 * @var \zhuravljov\yii\queue\Job[] $jobs
 */
?>
<h1>Pushed <?= count($jobs) ?> jobs</h1>

<?php \yii\helpers\VarDumper::dump($jobs, 10, true); ?>
