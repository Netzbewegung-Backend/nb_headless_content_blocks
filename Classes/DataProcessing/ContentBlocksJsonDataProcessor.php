<?php
declare(strict_types=1);

namespace Netzbewegung\NbHeadlessContentBlocks\DataProcessing;

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
        ContentObjectRenderer $cObj,
        array $contentObjectConfiguration,
        array $processorConfiguration,
        array $processedData
    ): array
    {
        $request = $cObj->getRequest();
        $this->contentBlockDataDecorator->setRequest($request);
        $table = $cObj->getCurrentTable();
        if (!$this->tableDefinitionCollection->hasTable($table)) {
            return $processedData;
        }
        $resolveRecord = $this->recordFactory->createResolvedRecordFromDatabaseRow(
            $table,
            $processedData['data'],
        );

        $contentTypeDefinition = $this->contentTypeResolver->resolve($resolveRecord);

        if ($contentTypeDefinition === null) {
            $processedData['data'] = $resolveRecord;
            return $processedData;
        }
        
        #$processedData['data'] = $this->contentBlockDataDecorator->decorate($resolveRecord);

        $as = $processorConfiguration['as'] ?? 'data';
        
        $tableDefinition = $this->tableDefinitionCollection->getTable($resolveRecord->getMainType());

        return [$as => new RecordJsonSerializable($resolveRecord, $tableDefinition, $this->tableDefinitionCollection)];
    }
    /*
      public function process(
      ContentObjectRenderer $cObj,
      array $contentObjectConfiguration,
      array $processorConfiguration,
      array $processedData
      ): array {
      $processedData = parent::process($cObj, $contentObjectConfiguration, $processorConfiguration, $processedData);
      debug($processedData); exit;
      $as = $processorConfiguration['as'] ?? 'data';

      return [$as => new ContentBlockDataJsonSerializable($processedData['data'])];
      }
     * 
     */
}
