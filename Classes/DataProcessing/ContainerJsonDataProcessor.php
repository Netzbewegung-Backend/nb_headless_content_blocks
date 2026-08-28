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

        $contents = [];

        foreach ($processedData[$as] as $contentElement) {
            $contents[] = $contentElement['renderedContent'];
        }

        $processedData[$as] = $contents;

        return $processedData;
    }
}
