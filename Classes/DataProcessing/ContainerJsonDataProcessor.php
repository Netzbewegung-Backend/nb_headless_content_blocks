<?php

declare(strict_types=1);

namespace Netzbewegung\NbHeadlessContentBlocks\DataProcessing;

use B13\Container\DataProcessing\ContainerProcessor;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\ContentObject\DataProcessorInterface;

readonly class ContainerJsonDataProcessor implements DataProcessorInterface
{
    public function __construct(
        protected ContainerProcessor $containerProcessor,
    ) {}

    public function process(
        ContentObjectRenderer $contentObjectRenderer,
        array $contentObjectConfiguration,
        array $processorConfiguration,
        array $processedData
    ): array {
        $processedData = $this->containerProcessor->process(
            $contentObjectRenderer,
            $contentObjectConfiguration,
            $processorConfiguration,
            $processedData
        );

        $as = $contentObjectRenderer->stdWrapValue('as', $processorConfiguration, 'children');

        // The b13 ContainerProcessor leaves the key unset when the container
        // cannot be built (hidden/moved record) and omits "renderedContent"
        // per child when "skipRenderingChildContent" is used.
        $children = is_array($processedData[$as] ?? null) ? $processedData[$as] : [];

        $contents = [];

        foreach ($children as $contentElement) {
            $contents[] = is_array($contentElement) ? ($contentElement['renderedContent'] ?? null) : null;
        }

        $processedData[$as] = $contents;

        return $processedData;
    }
}
