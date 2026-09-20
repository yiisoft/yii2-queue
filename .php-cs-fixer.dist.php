<?php

use PhpCsFixer\Config;
use PhpCsFixer\Finder;

$header = '@link https://www.yiiframework.com/
@copyright Copyright (c) 2008 Yii Software LLC
@license https://www.yiiframework.com/license/';

$finder = (new Finder())
    ->in([
        __DIR__ . '/src',
        __DIR__ . '/tests',
    ])
    ->notPath([
        '#(^|/)views/#',
        '#(^|/)config/#',
        '#(^|/)docker/#',
    ]);

return (new Config())
    ->setFinder($finder)
    ->setRules([
        'no_unused_imports' => true,
        'phpdoc_scalar' => true,
        'header_comment' => [
            'comment_type' => 'PHPDoc',
            'header' => $header,
            'location' => 'after_open',
        ],
    ]);
