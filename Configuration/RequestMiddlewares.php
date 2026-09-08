<?php

declare(strict_types=1);

use Netzbewegung\NbHeadlessContentBlocks\Middleware\SchemaEndpointMiddleware;

/**
 * The JSON Schema endpoint runs after site resolution (it reads the
 * per-site settings) and before page resolution (matching requests are
 * answered directly, everything else is passed through to regular page
 * rendering).
 */
return [
    'frontend' => [
        'nb-headless-content-blocks/schema-endpoint' => [
            'target' => SchemaEndpointMiddleware::class,
            'after' => [
                'typo3/cms-frontend/site',
            ],
            'before' => [
                'typo3/cms-frontend/base-redirect-resolver',
                'typo3/cms-frontend/page-resolver',
            ],
        ],
    ],
];
