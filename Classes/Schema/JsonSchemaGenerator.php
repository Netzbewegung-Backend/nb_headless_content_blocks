<?php

declare(strict_types=1);

namespace Netzbewegung\NbHeadlessContentBlocks\Schema;

use TYPO3\CMS\ContentBlocks\Definition\ContentType\ContentTypeInterface;
use TYPO3\CMS\ContentBlocks\Definition\TableDefinition;
use TYPO3\CMS\ContentBlocks\Definition\TableDefinitionCollection;
use TYPO3\CMS\ContentBlocks\Definition\TcaFieldDefinition;
use TYPO3\CMS\ContentBlocks\FieldType\CategoryFieldType;
use TYPO3\CMS\ContentBlocks\FieldType\CheckboxFieldType;
use TYPO3\CMS\ContentBlocks\FieldType\CollectionFieldType;
use TYPO3\CMS\ContentBlocks\FieldType\ColorFieldType;
use TYPO3\CMS\ContentBlocks\FieldType\DateTimeFieldType;
use TYPO3\CMS\ContentBlocks\FieldType\EmailFieldType;
use TYPO3\CMS\ContentBlocks\FieldType\FileFieldType;
use TYPO3\CMS\ContentBlocks\FieldType\FlexFormFieldType;
use TYPO3\CMS\ContentBlocks\FieldType\FolderFieldType;
use TYPO3\CMS\ContentBlocks\FieldType\JsonFieldType;
use TYPO3\CMS\ContentBlocks\FieldType\LinkFieldType;
use TYPO3\CMS\ContentBlocks\FieldType\NumberFieldType;
use TYPO3\CMS\ContentBlocks\FieldType\PasswordFieldType;
use TYPO3\CMS\ContentBlocks\FieldType\RelationFieldType;
use TYPO3\CMS\ContentBlocks\FieldType\SelectFieldType;
use TYPO3\CMS\ContentBlocks\FieldType\SlugFieldType;
use TYPO3\CMS\ContentBlocks\FieldType\TextareaFieldType;
use TYPO3\CMS\ContentBlocks\FieldType\TextFieldType;

final class JsonSchemaGenerator
{
    private const SCHEMA_DRAFT = 'http://json-schema.org/draft-07/schema#';

    private const SYSTEM_FIELDS = ['uid', 'pid', 'colPos', 'CType', 'foreign_table_parent_uid', 'tx_container_parent'];

    private array $recordDefinitions = [];

    private array $recordDefinitionsInProgress = [];

    public function __construct(
        private readonly TableDefinitionCollection $tableDefinitionCollection,
    ) {}

    /**
     * @return list<string>
     */
    public function getContentElementTypeNames(): array
    {
        if (!$this->tableDefinitionCollection->hasTable('tt_content')) {
            return [];
        }
        $typeNames = [];
        foreach ($this->getContentElementTableDefinition()->contentTypeDefinitionCollection as $typeDefinition) {
            $typeNames[] = (string)$typeDefinition->getTypeName();
        }
        return $typeNames;
    }

    public function generateCombined(string $idBase = ''): array
    {
        $this->reset();
        $branches = [];
        if ($this->tableDefinitionCollection->hasTable('tt_content')) {
            foreach ($this->getContentElementTableDefinition()->contentTypeDefinitionCollection as $typeDefinition) {
                $typeName = (string)$typeDefinition->getTypeName();
                $definitionKey = $this->definitionKey('ctype_' . $typeName);
                $this->recordDefinitions[$definitionKey] = $this->buildDataObject($typeDefinition);
                $branches[] = [
                    'type' => 'object',
                    'properties' => [
                        'id' => ['type' => 'integer'],
                        'type' => ['const' => $typeName],
                        'colPos' => ['type' => 'integer'],
                        'appearance' => ['type' => 'object'],
                        'data' => ['$ref' => '#/definitions/' . $definitionKey],
                    ],
                ];
            }
        }
        $schema = [
            '$schema' => self::SCHEMA_DRAFT,
            'title' => 'Content Block elements',
            'oneOf' => $branches,
            'definitions' => $this->getDefinitions(),
        ];
        if ($idBase !== '') {
            $schema['$id'] = rtrim($idBase, '/') . '/content-blocks.schema.json';
        }
        return $schema;
    }

    public function generateForTypeName(string $typeName, string $idBase = ''): ?array
    {
        $this->reset();
        if (!in_array($typeName, $this->getContentElementTypeNames(), true)) {
            return null;
        }
        foreach ($this->getContentElementTableDefinition()->contentTypeDefinitionCollection as $typeDefinition) {
            if ((string)$typeDefinition->getTypeName() !== $typeName) {
                continue;
            }
            $schema = [
                '$schema' => self::SCHEMA_DRAFT,
                'title' => $typeDefinition->getName(),
                'type' => 'object',
                'properties' => $this->buildPropertiesForColumns(
                    $this->getContentElementTableDefinition(),
                    $typeDefinition->getColumns()
                ),
                'definitions' => $this->getDefinitions(),
            ];
            if ($idBase !== '') {
                $schema['$id'] = rtrim($idBase, '/') . '/' . $typeName . '.schema.json';
            }
            return $schema;
        }
        return null;
    }

    private function reset(): void
    {
        $this->recordDefinitions = [];
        $this->recordDefinitionsInProgress = [];
    }

    private function getContentElementTableDefinition(): TableDefinition
    {
        return $this->tableDefinitionCollection->getTable('tt_content');
    }

    private function buildDataObject(ContentTypeInterface $typeDefinition): array
    {
        return [
            'type' => 'object',
            'properties' => $this->buildPropertiesForColumns(
                $this->getContentElementTableDefinition(),
                $typeDefinition->getColumns()
            ),
        ];
    }

    private function buildPropertiesForColumns(TableDefinition $tableDefinition, array $columns): array
    {
        $properties = [];
        foreach ($columns as $column) {
            if (in_array($column, self::SYSTEM_FIELDS, true)) {
                continue;
            }
            if (!$tableDefinition->tcaFieldDefinitionCollection->hasField($column)) {
                continue;
            }
            $field = $tableDefinition->tcaFieldDefinitionCollection->getField($column);
            if (in_array($field->identifier, self::SYSTEM_FIELDS, true)) {
                continue;
            }
            $properties[$field->identifier] = $this->mapField($field);
        }
        ksort($properties);
        return $properties;
    }

    private function mapField(TcaFieldDefinition $field): array
    {
        $config = $field->getTca()['config'] ?? [];
        $fieldType = $field->fieldType;

        if (
            $fieldType instanceof TextFieldType
            || $fieldType instanceof TextareaFieldType
            || $fieldType instanceof EmailFieldType
            || $fieldType instanceof ColorFieldType
            || $fieldType instanceof SlugFieldType
        ) {
            return ['type' => ['string', 'null']];
        }

        if ($fieldType instanceof NumberFieldType) {
            return ['type' => ['number', 'null']];
        }

        if ($fieldType instanceof DateTimeFieldType) {
            return ['type' => ['string', 'null'], 'format' => 'date-time'];
        }

        if ($fieldType instanceof SelectFieldType) {
            return ['anyOf' => [
                ['type' => 'string'],
                ['type' => 'array', 'items' => ['type' => 'string']],
            ]];
        }

        if ($fieldType instanceof PasswordFieldType) {
            return ['const' => ''];
        }

        if ($fieldType instanceof JsonFieldType) {
            return ['type' => ['object', 'array', 'null']];
        }

        if ($fieldType instanceof LinkFieldType) {
            return ['anyOf' => [
                ['$ref' => '#/definitions/linkObject'],
                ['type' => 'null'],
            ]];
        }

        if ($fieldType instanceof FileFieldType) {
            $file = ['$ref' => '#/definitions/fileObject'];
            if (($config['relationship'] ?? '') === 'oneToOne') {
                return ['anyOf' => [
                    $file,
                    ['$ref' => '#/definitions/errorObject'],
                    ['type' => 'null'],
                ]];
            }
            return ['type' => 'array', 'items' => ['anyOf' => [
                $file,
                ['$ref' => '#/definitions/errorObject'],
            ]]];
        }

        if ($fieldType instanceof FolderFieldType) {
            return ['type' => 'array', 'items' => ['type' => 'string']];
        }

        if ($fieldType instanceof CategoryFieldType) {
            if (($config['relationship'] ?? '') === 'oneToOne') {
                return ['anyOf' => [
                    ['$ref' => '#/definitions/categoryObject'],
                    ['type' => 'null'],
                ]];
            }
            return ['type' => 'array', 'items' => ['$ref' => '#/definitions/categoryObject']];
        }

        if ($fieldType instanceof CollectionFieldType) {
            $foreignTable = (string)($config['foreign_table'] ?? '');
            $itemSchema = ['type' => 'object'];
            if ($foreignTable !== '') {
                $itemSchema = $this->recordSchemaForTable($foreignTable);
            }
            return ['type' => 'array', 'items' => $itemSchema];
        }

        if ($fieldType instanceof RelationFieldType) {
            $foreignTable = (string)($config['foreign_table'] ?? '');
            $recordSchema = ['type' => 'object'];
            if ($foreignTable !== '') {
                $recordSchema = $this->recordSchemaForTable($foreignTable);
            }
            if (($config['relationship'] ?? '') === 'oneToOne') {
                return ['anyOf' => [
                    $recordSchema,
                    ['type' => 'null'],
                ]];
            }
            return ['type' => 'array', 'items' => $recordSchema];
        }

        if ($fieldType instanceof FlexFormFieldType) {
            return ['type' => ['object', 'null']];
        }

        if ($fieldType instanceof CheckboxFieldType) {
            return ['type' => 'null'];
        }

        return ['type' => 'null'];
    }

    private function recordSchemaForTable(string $table): array
    {
        $definitionKey = $this->definitionKey('record_' . $table);
        if (isset($this->recordDefinitionsInProgress[$definitionKey])) {
            return ['$ref' => '#/definitions/' . $definitionKey];
        }
        if (!$this->tableDefinitionCollection->hasTable($table)) {
            return ['type' => 'object'];
        }

        $this->recordDefinitionsInProgress[$definitionKey] = true;

        $tableDefinition = $this->tableDefinitionCollection->getTable($table);
        $properties = [];
        foreach ($tableDefinition->tcaFieldDefinitionCollection as $field) {
            if (in_array($field->identifier, self::SYSTEM_FIELDS, true)) {
                continue;
            }
            $properties[$field->identifier] = $this->mapField($field);
        }
        ksort($properties);

        $this->recordDefinitions[$definitionKey] = [
            'type' => 'object',
            'properties' => $properties,
        ];

        return ['$ref' => '#/definitions/' . $definitionKey];
    }

    private function getDefinitions(): array
    {
        return array_merge($this->getSharedDefinitions(), $this->recordDefinitions);
    }

    private function getSharedDefinitions(): array
    {
        return [
            'linkObject' => [
                'type' => 'object',
                'properties' => [
                    'url' => ['type' => 'string'],
                    'target' => ['type' => 'string'],
                    'type' => ['type' => 'string'],
                    'title' => ['type' => 'string'],
                    'config' => ['type' => 'object'],
                    'attr' => ['type' => 'object'],
                ],
            ],
            'fileObject' => [
                'type' => 'object',
                'properties' => [
                    'id' => ['type' => 'integer'],
                    'alt' => ['type' => ['string', 'null']],
                    'title' => ['type' => ['string', 'null']],
                    'publicUrl' => ['type' => 'string'],
                    'thumbnails' => [
                        'type' => 'object',
                        'additionalProperties' => ['type' => 'string'],
                    ],
                ],
            ],
            'errorObject' => [
                'type' => 'object',
                'required' => ['__errorMessage'],
                'properties' => [
                    '__errorMessage' => ['type' => 'string'],
                ],
            ],
            'categoryObject' => [
                'type' => 'object',
                'properties' => [
                    'uid' => ['type' => 'integer'],
                    'pid' => ['type' => 'integer'],
                    'title' => ['type' => 'string'],
                ],
            ],
        ];
    }

    private function definitionKey(string $prefix): string
    {
        return preg_replace('/[^a-zA-Z0-9_]/', '_', $prefix) ?? $prefix;
    }
}
