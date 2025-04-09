<?php
declare(strict_types=1);

namespace Netzbewegung\NbHeadlessContentBlocks\DataProcessing\JsonSerializable;

use JsonSerializable;
use TYPO3\CMS\ContentBlocks\Definition\TableDefinition;
use TYPO3\CMS\ContentBlocks\Definition\TableDefinitionCollection;
use TYPO3\CMS\Core\Domain\Record;
use TYPO3\CMS\Core\Resource\Exception\FileDoesNotExistException;

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
        try {
            $array = $this->record->toArray();
        } catch (FileDoesNotExistException $e) {
            return [
                '__errorMessage' => $e->getMessage()
            ];
        }

        $remove = ['uid', 'pid', 'colPos', 'CType', 'foreign_table_parent_uid'];

        foreach ($remove as $key) {
            unset($array[$key]);
        }

        return new ArrayRecursiveJsonSerializable($array, $this->tableDefinition, $this->tableDefinitionCollection);
    }
}
