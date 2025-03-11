<?php
declare(strict_types=1);

namespace Netzbewegung\NbHeadlessContentBlocks\DataProcessing\JsonSerializable;

use JsonSerializable;
use TYPO3\CMS\ContentBlocks\Definition\TableDefinition;
use TYPO3\CMS\ContentBlocks\Definition\TableDefinitionCollection;
use TYPO3\CMS\Core\Collection\LazyRecordCollection;

class LazyRecordCollectionJsonSerializable implements JsonSerializable
{

    public function __construct(
        protected LazyRecordCollection $lazyRecordCollection,
        protected TableDefinition $tableDefinition,
        protected TableDefinitionCollection $tableDefinitionCollection
    )
    {
        
    }

    public function jsonSerialize(): mixed
    {
        $data = [];
        foreach ($this->lazyRecordCollection as $key => $value) {
            $data[$key] = new RecordJsonSerializable($value, $this->tableDefinition, $this->tableDefinitionCollection);
        }

        return $data;
    }
}
