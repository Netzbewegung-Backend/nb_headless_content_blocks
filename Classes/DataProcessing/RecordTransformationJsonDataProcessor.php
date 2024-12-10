<?php

declare(strict_types=1);

namespace Netzbewegung\NbHeadlessContentBlocks\DataProcessing;

use Netzbewegung\NbHeadlessContentBlocks\DataProcessing\JsonSerializable\RecordTransformation\RecordJsonSerializable;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\DataProcessing\RecordTransformationProcessor;
use function debug;

readonly class RecordTransformationJsonDataProcessor extends RecordTransformationProcessor
{
    public function process(
        ContentObjectRenderer $cObj,
        array $contentObjectConfiguration,
        array $processorConfiguration,
        array $processedData
    ): array {
        $processedData = parent::process($cObj, $contentObjectConfiguration, $processorConfiguration, $processedData);

        $as = $processorConfiguration['as'] ?? 'data';

        return [$as => new RecordJsonSerializable($processedData['data'])];
    }
}
