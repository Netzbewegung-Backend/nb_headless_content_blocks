<?php

declare(strict_types=1);

namespace Netzbewegung\NbHeadlessContentBlocks\DataProcessing;

use Netzbewegung\NbHeadlessContentBlocks\DataProcessing\JsonSerializable\ContentBlockDataJsonSerializable;
use TYPO3\CMS\ContentBlocks\DataProcessing\ContentBlocksDataProcessor;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;

readonly class ContentBlocksJsonDataProcessor extends ContentBlocksDataProcessor
{
    public function process(
        ContentObjectRenderer $cObj,
        array $contentObjectConfiguration,
        array $processorConfiguration,
        array $processedData
    ): array {
        $processedData = parent::process($cObj, $contentObjectConfiguration, $processorConfiguration, $processedData);

        $as = $processorConfiguration['as'] ?? 'data';

        return [$as => new ContentBlockDataJsonSerializable($processedData['data'])];
    }
}
