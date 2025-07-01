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
    ) {}

    public function toArray(): array
    {
        $data = [];

        foreach ($this->lazyRecordCollection as $key => $value) {
            $data[$key] = GeneralUtility::makeInstance(RecordToArray::class, $value, $this->tableDefinition, $this->tableDefinitionCollection)->toArray();
        }

        return $data;
    }
}
