<?php

declare(strict_types=1);

namespace Netzbewegung\NbHeadlessContentBlocks\DataProcessing\ToArray;

use TYPO3\CMS\ContentBlocks\Definition\TableDefinition;
use TYPO3\CMS\ContentBlocks\Definition\TableDefinitionCollection;
use TYPO3\CMS\Core\Domain\Record;
use TYPO3\CMS\Core\EventDispatcher\EventDispatcher;
use TYPO3\CMS\Core\Resource\Exception\FileDoesNotExistException;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class RecordToArray
{
    public function __construct(
        protected Record $record,
        protected ?TableDefinition $tableDefinition,
        protected TableDefinitionCollection $tableDefinitionCollection,
        protected readonly EventDispatcher $eventDispatcher
    ) {

    }

    public function toArray(): array
    {
        try {
            $array = $this->record->toArray();
        } catch (FileDoesNotExistException $fileDoesNotExistException) {
            return [
                '__errorMessage' => $fileDoesNotExistException->getMessage()
            ];
        }

        $remove = ['uid', 'pid', 'colPos', 'CType', 'foreign_table_parent_uid', 'tx_container_parent'];

        foreach ($remove as $key) {
            unset($array[$key]);
        }

        return GeneralUtility::makeInstance(
            ArrayRecursiveToArray::class,
            $array,
            $this->tableDefinition,
            $this->tableDefinitionCollection,
            $this->eventDispatcher
        )->toArray();
    }
}
