<?php

declare(strict_types=1);

/*
 * (c) INSPIRED MINDS
 */

$GLOBALS['TL_DCA']['tl_files']['fields']['autoDeleteFilesTime'] = [
    'exclude' => true,
    'inputType' => 'inputUnit',
    'options' => ['hour', 'day', 'month', 'year'],
    'reference' => &$GLOBALS['TL_LANG']['tl_files']['autoDeleteFilesTimeOptions'],
    'eval' => ['maxlength' => 200, 'rgxp' => 'natural', 'tl_class' => 'w50 clr'],
    'sql' => ['type' => 'string', 'length' => 255, 'default' => ''],
];

$GLOBALS['TL_DCA']['tl_files']['fields']['autoDeleteFilesCount'] = [
    'exclude' => true,
    'inputType' => 'text',
    'eval' => ['maxlength' => 255, 'rgxp' => 'natural', 'tl_class' => 'w50'],
    'sql' => ['type' => 'string', 'length' => 255, 'default' => ''],
];
