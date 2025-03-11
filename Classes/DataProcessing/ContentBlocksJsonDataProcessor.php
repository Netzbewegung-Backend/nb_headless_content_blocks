<?php
declare(strict_types=1);

namespace Netzbewegung\NbHeadlessContentBlocks\DataProcessing;

use TYPO3\CMS\ContentBlocks\Definition\ContentType\ContentTypeInterface;
use Netzbewegung\NbHeadlessContentBlocks\DataProcessing\JsonSerializable\RecordJsonSerializable;
use TYPO3\CMS\ContentBlocks\DataProcessing\ContentBlockDataDecorator;
use TYPO3\CMS\ContentBlocks\DataProcessing\ContentTypeResolver;
use TYPO3\CMS\ContentBlocks\Definition\TableDefinitionCollection;
use TYPO3\CMS\Core\Domain\RecordFactory;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\ContentObject\DataProcessorInterface;

readonly class ContentBlocksJsonDataProcessor implements DataProcessorInterface
{

    public function __construct(
        protected TableDefinitionCollection $tableDefinitionCollection,
        protected RecordFactory $recordFactory,
        protected ContentBlockDataDecorator $contentBlockDataDecorator,
        protected ContentTypeResolver $contentTypeResolver,
    )
    {
        
    }

    public function process(
        ContentObjectRenderer $contentObjectRenderer,
        array $contentObjectConfiguration,
        array $processorConfiguration,
        array $processedData
    ): array
    {
        $serverRequest = $contentObjectRenderer->getRequest();
        $this->contentBlockDataDecorator->setRequest($serverRequest);
        $table = $contentObjectRenderer->getCurrentTable();
        if (!$this->tableDefinitionCollection->hasTable($table)) {
            return $processedData;
        }

        $resolveRecord = $this->recordFactory->createResolvedRecordFromDatabaseRow(
            $table,
            $processedData['data'],
        );

        $contentTypeDefinition = $this->contentTypeResolver->resolve($resolveRecord);

        if (!$contentTypeDefinition instanceof ContentTypeInterface) {
            $processedData['data'] = $resolveRecord;
            return $processedData;
        }

        $as = $processorConfiguration['as'] ?? 'data';
        
        $tableDefinition = $this->tableDefinitionCollection->getTable($resolveRecord->getMainType());

        return [$as => new RecordJsonSerializable($resolveRecord, $tableDefinition, $this->tableDefinitionCollection)];
    }
}
