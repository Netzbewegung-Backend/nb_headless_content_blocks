<?php
declare(strict_types=1);

namespace Netzbewegung\NbHeadlessContentBlocks\DataProcessing\JsonSerializable;

use Exception;
use JsonSerializable;
use TYPO3\CMS\ContentBlocks\Definition\TableDefinition;
use TYPO3\CMS\ContentBlocks\Definition\TableDefinitionCollection;
use TYPO3\CMS\Core\Collection\LazyRecordCollection;
use TYPO3\CMS\Core\Domain\FlexFormFieldValues;
use TYPO3\CMS\Core\Domain\Record;
use TYPO3\CMS\Core\LinkHandling\TypolinkParameter;
use TYPO3\CMS\Core\Resource\Collection\LazyFileReferenceCollection;
use TYPO3\CMS\Core\Resource\FileReference;

class ArrayRecursiveJsonSerializable implements JsonSerializable
{

    public function __construct(
        protected array $array,
        protected TableDefinition $tableDefinition,
        protected TableDefinitionCollection $tableDefinitionCollection
    )
    {
        
    }

    public function jsonSerialize(): mixed
    {
        $data = [];

        foreach ($this->array as $key => $value) {

            if ($this->tableDefinition->getTcaFieldDefinitionCollection()->hasField($key)) {
                $tcaFieldDefinition = $this->tableDefinition->getTcaFieldDefinitionCollection()->getField($key);
                $decoratedKey = $tcaFieldDefinition->getIdentifier();
            } else {
                $decoratedKey = $key;
            }

            switch (true) {
                case is_array($value):
                    $data[$decoratedKey] = new ArrayRecursiveJsonSerializable($value);
                    break;
                case $value instanceof Record:
                    $tableDefinition = $this->getTableDefinitionByKey($key);
                    $data[$decoratedKey] = new RecordJsonSerializable($value, $tableDefinition, $this->tableDefinitionCollection);
                    break;
                case $value instanceof FlexFormFieldValues:
                    $data[$decoratedKey] = $value->toArray();
                    break;
                case $value instanceof TypolinkParameter:
                    $data[$decoratedKey] = new TypolinkParameterJsonSerializable($value);
                    break;
                case $value instanceof LazyRecordCollection:
                    $tableDefinition = $this->getTableDefinitionByKey($key);
                    $data[$decoratedKey] = new LazyRecordCollectionJsonSerializable($value, $tableDefinition, $this->tableDefinitionCollection);
                    break;
                case $value instanceof LazyFileReferenceCollection:
                    $data[$decoratedKey] = new LazyFileReferenceCollectionJsonSerializable($value);
                    break;
                case $value instanceof FileReference:
                    $data[$decoratedKey] = new FileReferenceJsonSerializable($value);
                    break;
                default:
                    if ($this->tableDefinition->getTcaFieldDefinitionCollection()->hasField($key)) {
                        $tcaFieldDefinition = $this->tableDefinition->getTcaFieldDefinitionCollection()->getField($key);
                        $fieldType = $tcaFieldDefinition->getFieldType();
                    }
                            
                    $data[$decoratedKey] = new MiscJsonSerializable($value, $fieldType);
                    break;
            }
        }

        ksort($data);

        return $data;
    }

    protected function getTableDefinitionByKey(string $key): TableDefinition
    {
        $tableName = $this->getTableNameByKey($key);

        return $this->tableDefinitionCollection->getTable($tableName);
    }

    protected function getTableNameByKey(string $key): string
    {
        if ($this->tableDefinitionCollection->hasTable($key)) {
            return $key;
        }

        if ($this->tableDefinition->getTcaFieldDefinitionCollection()->hasField($key)) {
            $tca = $this->tableDefinition->getTcaFieldDefinitionCollection()->getField($key)->getFieldType()->getTca();
            return $tca['config']['foreign_table'];
        }

        throw new Exception('Unknown case in ->getTableNameByKey() for key "' . $key . '"', 5059397727);
    }
}
