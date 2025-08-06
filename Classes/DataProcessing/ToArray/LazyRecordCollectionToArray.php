<?php

declare(strict_types=1);

namespace Netzbewegung\NbHeadlessContentBlocks\DataProcessing\ToArray;

use TYPO3\CMS\ContentBlocks\Definition\TableDefinition;
use TYPO3\CMS\ContentBlocks\Definition\TableDefinitionCollection;
use TYPO3\CMS\Core\Collection\LazyRecordCollection;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class LazyRecordCollectionToArray
{

    public function __construct(
        protected LazyRecordCollection $lazyRecordCollection,
        protected ?TableDefinition $tableDefinition,
        protected TableDefinitionCollection $tableDefinitionCollection
    ) {

    }

    public function toArray(): array
    {
        $data = [];

        foreach ($this->lazyRecordCollection as $key => $value) {
            if ($this->tableDefinition === null) {
                $tableName = $value->getRawRecord()->getMainType();
                if ($tableName === 'sys_category') {
                    $tableDefinition = null;
                } else if ($this->tableDefinitionCollection->hasTable($tableName)) {
                    $tableDefinition = $this->tableDefinitionCollection->getTable($tableName);
                } else {
                    #debug($this->lazyRecordCollection);
                    throw new \Exception('Unknown case in LazyRecordCollectionToArray->toArray() switch for key "' . $key . '"', 1746095968);
                }
            } else {
                $tableDefinition = $this->tableDefinition;
            }
            $data[$key] = GeneralUtility::makeInstance(RecordToArray::class, $value, $tableDefinition, $this->tableDefinitionCollection)->toArray();
        }

        return $data;
    }
}
