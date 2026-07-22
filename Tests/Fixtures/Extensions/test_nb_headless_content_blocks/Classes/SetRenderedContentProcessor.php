<?php

declare(strict_types=1);

namespace Typo3tests\TestNbHeadlessContentBlocks;

use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\ContentObject\DataProcessorInterface;

/**
 * Test stub: simulates the renderedContent usually created by RECORDS
 * rendering inside b13/container's ContainerProcessor.
 */
class SetRenderedContentProcessor implements DataProcessorInterface
{
    public function process(
        ContentObjectRenderer $contentObjectRenderer,
        array $contentObjectConfiguration,
        array $processorConfiguration,
        array $processedData
    ): array {
        $processedData['renderedContent'] = 'STUB:' . ($processedData['header'] ?? '');

        return $processedData;
    }
}
