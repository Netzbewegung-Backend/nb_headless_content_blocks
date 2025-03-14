<?php
declare(strict_types=1);

namespace Netzbewegung\NbHeadlessContentBlocks\DataProcessing\JsonSerializable;

use JsonSerializable;
use TYPO3\CMS\ContentBlocks\Definition\TableDefinition;
use TYPO3\CMS\ContentBlocks\Definition\TableDefinitionCollection;
use TYPO3\CMS\Core\Domain\Record;

class RecordJsonSerializable implements JsonSerializable
{

    public function __construct(
        protected Record $record,
        protected TableDefinition $tableDefinition,
        protected TableDefinitionCollection $tableDefinitionCollection
    )
    {
        
    }

    public function jsonSerialize(): mixed
    {
        $array = $this->record->toArray();

        $remove = ['uid', 'pid', 'colPos', 'CType', 'foreign_table_parent_uid'];
        
        foreach ($remove as $key) {
            unset($array[$key]);
        }

        return new ArrayRecursiveJsonSerializable($array, $this->tableDefinition, $this->tableDefinitionCollection);
    }
}
