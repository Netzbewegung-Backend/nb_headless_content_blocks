<?php

declare(strict_types=1);

namespace Netzbewegung\NbHeadlessContentBlocks\DataProcessing;

use B13\Container\DataProcessing\ContainerProcessor;
use TYPO3\CMS\ContentBlocks\DataProcessing\ContentBlockDataDecorator;
use TYPO3\CMS\ContentBlocks\DataProcessing\ContentTypeResolver;
use TYPO3\CMS\ContentBlocks\Definition\TableDefinitionCollection;
use TYPO3\CMS\ContentBlocks\Registry\ContentBlockRegistry;
use TYPO3\CMS\Core\Domain\RecordFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\ContentObject\DataProcessorInterface;

readonly class ContainerJsonDataProcessor implements DataProcessorInterface
{
    public function __construct(
        protected TableDefinitionCollection $tableDefinitionCollection,
        protected RecordFactory $recordFactory,
        protected ContentBlockDataDecorator $contentBlockDataDecorator,
        protected ContentTypeResolver $contentTypeResolver,
        protected ContentBlockRegistry $contentBlockRegistry,
    ) {}

    public function process(
        ContentObjectRenderer $contentObjectRenderer,
        array $contentObjectConfiguration,
        array $processorConfiguration,
        array $processedData
    ): array {
        $processedData = GeneralUtility::makeInstance(ContainerProcessor::class)->process(
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
