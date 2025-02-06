<?php
declare(strict_types=1);

namespace Netzbewegung\NbHeadlessContentBlocks\DataProcessing\JsonSerializable;

use JsonSerializable;
use TYPO3\CMS\ContentBlocks\Definition\TableDefinition;
use TYPO3\CMS\ContentBlocks\Definition\TableDefinitionCollection;
use TYPO3\CMS\ContentBlocks\FieldType\FileFieldType;
use TYPO3\CMS\ContentBlocks\FieldType\SelectFieldType;
use TYPO3\CMS\ContentBlocks\FieldType\TextareaFieldType;
use TYPO3\CMS\ContentBlocks\FieldType\TextFieldType;
use TYPO3\CMS\Core\Collection\LazyRecordCollection;
use TYPO3\CMS\Core\Domain\FlexFormFieldValues;
use TYPO3\CMS\Core\Domain\Record;
use TYPO3\CMS\Core\LinkHandling\TypolinkParameter;
use TYPO3\CMS\Core\Resource\Collection\LazyFileReferenceCollection;
use TYPO3\CMS\Core\Resource\FileReference;
use function debug;

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
            switch (true) {
                case is_array($value):
                    $data[$key] = new ArrayRecursiveJsonSerializable($value);
                    break;
                case $value instanceof Record:
                    $tableDefinition = $this->getTableDefinitionByKey($key);
                    $data[$key] = new RecordJsonSerializable($value, $tableDefinition, $this->tableDefinitionCollection);
                    break;
                case $value instanceof FlexFormFieldValues:
                    $data[$key] = $value->toArray();
                    break;
                case $value instanceof TypolinkParameter:
                    $data[$key] = new TypolinkParameterJsonSerializable($value);
                    break;
                case $value instanceof LazyRecordCollection:
                    $tableDefinition = $this->getTableDefinitionByKey($key);
                    $data[$key] = new LazyRecordCollectionJsonSerializable($value, $tableDefinition, $this->tableDefinitionCollection);
                    break;
                case $value instanceof LazyFileReferenceCollection:
                    $data[$key] = new LazyFileReferenceCollectionJsonSerializable($value);
                    break;
                case $value instanceof FileReference:
                    $data[$key] = new FileReferenceJsonSerializable($value);
                    break;
                default:
                    if ($this->tableDefinition->getTcaFieldDefinitionCollection()->hasField($key)) {
                        $tcaFieldDefinition = $this->tableDefinition->getTcaFieldDefinitionCollection()->getField($key);
                        $fieldType = $tcaFieldDefinition->getFieldType();

                        switch (true) {
                            case $fieldType instanceof FileFieldType;
                            case $fieldType instanceof SelectFieldType:
                            case $fieldType instanceof TextFieldType:
                                break;
                            case $fieldType instanceof TextareaFieldType:
                                if ($fieldType->getTca()['config']['enableRichtext'] === true) {
                                    // @todo parse HTML
                                }
                                break;
                            default:
                                debug($value, $key);
                                debug('DEFAULT');
                                debug($fieldType, $key);
                                exit;
                        }
                    }

                    $data[$key] = $value;
            }
        }

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
            #debug($table, '$key is table');
            return $key;
        } 
        
        if ($this->tableDefinition->getTcaFieldDefinitionCollection()->hasField($key)) {
            #debug($table, '$field TCA is table');
            $tca = $this->tableDefinition->getTcaFieldDefinitionCollection()->getField($key)->getFieldType()->getTca();
            return $tca['config']['foreign_table'];
        }

        debug($key);
        debug('UNKNOW ELSE getTableByKey');
        exit;
    }
}
