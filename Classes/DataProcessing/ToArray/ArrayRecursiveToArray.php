<?php

declare(strict_types=1);

namespace Netzbewegung\NbHeadlessContentBlocks\DataProcessing\ToArray;

use TYPO3\CMS\ContentBlocks\Definition\TcaFieldDefinition;
use DateTimeImmutable;
use Exception;
use TYPO3\CMS\ContentBlocks\Definition\TableDefinition;
use TYPO3\CMS\ContentBlocks\Definition\TableDefinitionCollection;
use TYPO3\CMS\ContentBlocks\FieldType\CategoryFieldType;
use TYPO3\CMS\ContentBlocks\FieldType\ColorFieldType;
use TYPO3\CMS\ContentBlocks\FieldType\EmailFieldType;
use TYPO3\CMS\ContentBlocks\FieldType\JsonFieldType;
use TYPO3\CMS\ContentBlocks\FieldType\PassFieldType;
use TYPO3\CMS\ContentBlocks\FieldType\PasswordFieldType;
use TYPO3\CMS\ContentBlocks\FieldType\SelectFieldType;
use TYPO3\CMS\ContentBlocks\FieldType\SlugFieldType;
use TYPO3\CMS\ContentBlocks\FieldType\TextareaFieldType;
use TYPO3\CMS\ContentBlocks\FieldType\TextFieldType;
use TYPO3\CMS\ContentBlocks\FieldType\UuidFieldType;
use TYPO3\CMS\Core\Collection\LazyRecordCollection;
use TYPO3\CMS\Core\Domain\FlexFormFieldValues;
use TYPO3\CMS\Core\Domain\Record;
use TYPO3\CMS\Core\LinkHandling\TypolinkParameter;
use TYPO3\CMS\Core\Resource\Collection\LazyFileReferenceCollection;
use TYPO3\CMS\Core\Resource\Collection\LazyFolderCollection;
use TYPO3\CMS\Core\Resource\FileReference;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;

class ArrayRecursiveToArray
{
    public function __construct(
        protected array $array,
        protected ?TableDefinition $tableDefinition,
        protected TableDefinitionCollection $tableDefinitionCollection
    ) {

    }

    public function toArray(): array
    {
        $data = [];

        foreach ($this->array as $key => $value) {

            if ($this->tableDefinition instanceof TableDefinition && $this->tableDefinition->tcaFieldDefinitionCollection->hasField($key)) {
                $tcaFieldDefinition = $this->tableDefinition->tcaFieldDefinitionCollection->getField($key);
                $decoratedKey = $tcaFieldDefinition->identifier;
            } else {
                $tcaFieldDefinition = null;
                $decoratedKey = $key;
            }

            switch (true) {
                case is_null($value):
                case is_int($value):
                    $data[$decoratedKey] = $value;
                    break;
                case is_array($value):
                    if ($tcaFieldDefinition instanceof TcaFieldDefinition && $tcaFieldDefinition->fieldType instanceof JsonFieldType) {
                        $data[$decoratedKey] = $value;
                    } else {
                        $data[$decoratedKey] = GeneralUtility::makeInstance(ArrayRecursiveToArray::class, $value, null, $this->tableDefinitionCollection)->toArray();
                    }

                    break;
                case is_string($value):
                    $data[$decoratedKey] = $this->processStringField($value, $key);
                    break;
                case $value instanceof DateTimeImmutable:
                    $data[$decoratedKey] = $value->format(DateTimeImmutable::W3C);
                    break;
                case $value instanceof Record:
                    $tableDefinition = $this->getTableDefinitionByKey($key);
                    $data[$decoratedKey] = GeneralUtility::makeInstance(RecordToArray::class, $value, $tableDefinition, $this->tableDefinitionCollection)->toArray();
                    break;
                case $value instanceof FlexFormFieldValues:
                    $data[$decoratedKey] = $value->toArray();
                    break;
                case $value instanceof TypolinkParameter:
                    $data[$decoratedKey] = GeneralUtility::makeInstance(TypolinkParameterToArray::class, $value)->toArray();
                    break;
                case $value instanceof LazyRecordCollection:
                    $tableName = $this->getTableNameByKey($key);
                    if ($tableName === 'sys_category') {
                        $data[$decoratedKey] = GeneralUtility::makeInstance(LazyRecordCollectionSysCategoryToArray::class, $value)->toArray();
                    } else {
                        $tableDefinition = $this->getTableDefinitionByKey($key);
                        $data[$decoratedKey] = GeneralUtility::makeInstance(LazyRecordCollectionToArray::class, $value, $tableDefinition, $this->tableDefinitionCollection)->toArray();
                    }

                    break;
                case $value instanceof LazyFileReferenceCollection:
                    $data[$decoratedKey] = GeneralUtility::makeInstance(LazyFileReferenceCollectionToArray::class, $value)->toArray();
                    break;
                case $value instanceof FileReference:
                    $data[$decoratedKey] = GeneralUtility::makeInstance(FileReferenceToArray::class, $value)->toArray();
                    break;
                case $value instanceof LazyFolderCollection:
                    $data[$decoratedKey] = GeneralUtility::makeInstance(LazyFolderCollectionToArray::class, $value)->toArray();
                    break;
                default:
                    #debug($value);
                    throw new Exception('Unknown case in ->toArray() switch for key "' . $key . '"', 1746095968);
            }
        }

        ksort($data);

        return $data;
    }

    protected function getTableDefinitionByKey(string $key): ?TableDefinition
    {
        $tableName = $this->getTableNameByKey($key);

        if ($tableName === null) {
            return null;
        }

        if ($this->tableDefinitionCollection->hasTable($tableName)) {
            return $this->tableDefinitionCollection->getTable($tableName);
        }

        return null;
    }

    protected function getTableNameByKey(string $key): ?string
    {
        if ($this->tableDefinitionCollection->hasTable($key)) {
            return $key;
        }

        if ($this->tableDefinition instanceof TableDefinition && $this->tableDefinition->tcaFieldDefinitionCollection->hasField($key)) {
            $field = $this->tableDefinition->tcaFieldDefinitionCollection->getField($key);
            $fieldType = $field->fieldType;

            if ($fieldType instanceof CategoryFieldType) {
                return 'sys_category';
            }

            $tca = $fieldType->getTca();
            if (isset($tca['config']['foreign_table'])) {
                return $tca['config']['foreign_table'];
            }

            if (isset($tca['config']['allowed'])) {
                if (count(explode(',', $tca['config']['allowed'])) > 1) {
                    return null;
                }
                return $tca['config']['allowed'];
            }
        }

        return null;
    }

    protected function processStringField(string $value, int|string $key): string
    {
        if (!$this->tableDefinition instanceof TableDefinition || is_int($key) || $this->tableDefinition->tcaFieldDefinitionCollection->hasField($key) === false) {
            return $value;
        }

        $tcaFieldDefinition = $this->tableDefinition->tcaFieldDefinitionCollection->getField($key);
        $fieldType = $tcaFieldDefinition->fieldType;

        switch (true) {
            case $fieldType instanceof ColorFieldType:
            case $fieldType instanceof SelectFieldType:
            case $fieldType instanceof TextFieldType:
            case $fieldType instanceof EmailFieldType:
            case $fieldType instanceof PassFieldType:
            case $fieldType instanceof SlugFieldType:
            case $fieldType instanceof UuidFieldType:
                break;
            case $fieldType instanceof PasswordFieldType:
                // Unclear in which case it makes sense to send a password via headless to client.
                // So we currently unset the value.
                $value = '';
                break;
            case $fieldType instanceof TextareaFieldType:
                $enableRichtext = $fieldType->getTca()['config']['enableRichtext'] ?? false;
                if ($enableRichtext === true) {
                    $contentObject = GeneralUtility::makeInstance(ContentObjectRenderer::class);
                    return $contentObject->parseFunc($value, null, '< lib.parseFunc_RTE');
                }

                break;
            default:
                #debug($fieldType);
                throw new Exception('Unknown default case in ->processStringField() for key "' . $key . '"', 1746095966);
        }

        return $value;
    }
}
