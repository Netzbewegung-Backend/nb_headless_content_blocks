<?php
declare(strict_types=1);

namespace Netzbewegung\NbHeadlessContentBlocks\DataProcessing\ToArray;

use Exception;
use TYPO3\CMS\ContentBlocks\Definition\TableDefinition;
use TYPO3\CMS\ContentBlocks\Definition\TableDefinitionCollection;
use TYPO3\CMS\Core\Collection\LazyRecordCollection;
use TYPO3\CMS\Core\Domain\FlexFormFieldValues;
use TYPO3\CMS\Core\Domain\Record;
use TYPO3\CMS\Core\LinkHandling\TypolinkParameter;
use TYPO3\CMS\Core\Resource\Collection\LazyFileReferenceCollection;
use TYPO3\CMS\Core\Resource\FileReference;
use TYPO3\CMS\ContentBlocks\FieldType\FileFieldType;
use TYPO3\CMS\ContentBlocks\FieldType\SelectFieldType;
use TYPO3\CMS\ContentBlocks\FieldType\TextareaFieldType;
use TYPO3\CMS\ContentBlocks\FieldType\TextFieldType;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;

class ArrayRecursiveToArray
{

    public function __construct(
        protected array $array,
        protected TableDefinition $tableDefinition,
        protected TableDefinitionCollection $tableDefinitionCollection
    )
    {
        
    }

    public function toArray(): array
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
                    $data[$decoratedKey] = (new ArrayRecursiveToArray($value))->toArray();
                    break;
                case is_string($value):
                    $data[$decoratedKey] = $this->processStringField($value, $key);
                    break;
                case $value instanceof Record:
                    $tableDefinition = $this->getTableDefinitionByKey($key);
                    $data[$decoratedKey] = (new RecordToArray($value, $tableDefinition, $this->tableDefinitionCollection))->toArray();
                    break;
                case $value instanceof FlexFormFieldValues:
                    $data[$decoratedKey] = $value->toArray();
                    break;
                case $value instanceof TypolinkParameter:
                    $data[$decoratedKey] = (new TypolinkParameterToArray($value))->toArray();
                    break;
                case $value instanceof LazyRecordCollection:
                    $tableDefinition = $this->getTableDefinitionByKey($key);
                    $data[$decoratedKey] = (new LazyRecordCollectionToArray($value, $tableDefinition, $this->tableDefinitionCollection))->toArray();
                    break;
                case $value instanceof LazyFileReferenceCollection:
                    $data[$decoratedKey] = (new LazyFileReferenceCollectionToArray($value))->toArray();
                    break;
                case $value instanceof FileReference:
                    $data[$decoratedKey] = (new FileReferenceToArray($value))->toArray();
                    break;
                default:
                    $data[$decoratedKey] = $value;
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

    protected function processStringField(string $value, string $key): string
    {
        if ($this->tableDefinition->getTcaFieldDefinitionCollection()->hasField($key) === false) {
            return $value;
        }

        $tcaFieldDefinition = $this->tableDefinition->getTcaFieldDefinitionCollection()->getField($key);
        $fieldType = $tcaFieldDefinition->getFieldType();

        switch (true) {
            #case $fieldType instanceof FileFieldType;
            case $fieldType instanceof SelectFieldType:
            case $fieldType instanceof TextFieldType:
                break;
            case $fieldType instanceof TextareaFieldType:
                $enableRichtext = $fieldType->getTca()['config']['enableRichtext'] ?? false;
                if ($enableRichtext === true) {
                    $contentObject = GeneralUtility::makeInstance(ContentObjectRenderer::class);
                    return $contentObject->parseFunc($this->value, null, '< lib.parseFunc_RTE');
                }

                break;
            default:
            # just return value
            #throw new Exception('Unknown default case in ArrayRecursiveToArray default case for key "' . $key . '"', 6848262796);
        }

        return $value;
    }
}
