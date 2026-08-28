<?php

declare(strict_types=1);

defined('TYPO3') or die();

\TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\B13\Container\Tca\Registry::class)->configureContainer(
    new \B13\Container\Tca\ContainerConfiguration(
        'test_2cols_container',
        '2 Column Test Container',
        'Container for functional tests',
        [
            [
                ['name' => 'left side', 'colPos' => 201],
                ['name' => 'right side', 'colPos' => 202],
            ],
        ]
    )
);

// Container that is a Content Block at the same time (production pattern:
// container content blocks with the columns nested inside "data").
// Registered WITHOUT Registry::configureContainer(), because that would
// overwrite the Content Block's "showitem" (and thereby its fields in the
// resolved record); writing containerConfiguration directly keeps both.
$containerConfigurationBlock = new \B13\Container\Tca\ContainerConfiguration(
    'test_containerblock',
    'Container Content Block',
    'Container registered as Content Block for functional tests',
    [
        [
            ['name' => 'left', 'colPos' => 211],
            ['name' => 'right', 'colPos' => 212],
        ],
    ]
);
$GLOBALS['TCA']['tt_content']['containerConfiguration']['test_containerblock'] = $containerConfigurationBlock->toArray();
