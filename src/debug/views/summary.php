<?php
/**
 * @var \yii\web\View $this
 * @var string $url
 * @var int $count
 */
?>
<div class="yii-debug-toolbar__block">
    <a href="<?= $url ?>">
        Queue
        <span class="yii-debug-toolbar__label yii-debug-toolbar__label_<?= $count ? 'info' : 'default' ?>">
            <?= $count ?>
        </span>
    </a>
</div>

