<?php

declare(strict_types=1);

namespace Netzbewegung\NbHeadlessContentBlocks\Schema;

use Netzbewegung\NbHeadlessContentBlocks\ContentBlocks\HeadlessYamlLoader;
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
    private const SCHEMA_DRAFT = 'https://json-schema.org/draft/2020-12/schema';

    private const SYSTEM_FIELDS = ['uid', 'pid', 'colPos', 'CType', 'foreign_table_parent_uid', 'tx_container_parent'];

    private const ELEMENT_DEFINITION_KEY = 'contentBlockElement';

    private array $recordDefinitions = [];

    private array $recordDefinitionsInProgress = [];

    private bool $elementDefinitionRequired = false;

    public function __construct(
        private readonly TableDefinitionCollection $tableDefinitionCollection,
        private readonly HeadlessYamlLoader $headlessYamlLoader,
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
        // sorted, so generated artifacts stay byte-stable across environments
        sort($typeNames);
        return $typeNames;
    }

    /**
     * @param list<string> $tcaTypeNames tt_content type names registered in
     *        TCA; every name without a Content Block definition becomes a
     *        loose fallback branch (core types like "html" or "shortcut",
     *        classic plugins) so full page columns validate against the schema
     */
    public function generateCombined(string $idBase = '', array $tcaTypeNames = []): array
    {
        $this->reset();
        $this->buildElementDefinition($tcaTypeNames);
        $schema = [
            '$schema' => self::SCHEMA_DRAFT,
            'title' => 'Content Block elements',
            '$ref' => '#/$defs/' . self::ELEMENT_DEFINITION_KEY,
            '$defs' => $this->getDefinitions(),
        ];
        if ($idBase !== '') {
            $schema['$id'] = rtrim($idBase, '/') . '/content-blocks.schema.json';
        }
        return $schema;
    }

    /**
     * @param list<string> $tcaTypeNames tt_content type names registered in
     *        TCA, used for fallback branches when the block renders children
     */
    public function generateForTypeName(string $typeName, string $idBase = '', array $tcaTypeNames = []): ?array
    {
        $this->reset();
        if (!in_array($typeName, $this->getContentElementTypeNames(), true)) {
            return null;
        }
        foreach ($this->getContentElementTableDefinition()->contentTypeDefinitionCollection as $typeDefinition) {
            if ((string)$typeDefinition->getTypeName() !== $typeName) {
                continue;
            }
            $dataObject = $this->buildDataObject($typeDefinition);
            if ($this->elementDefinitionRequired) {
                // the block declares rendered children, so the schema needs
                // the recursive element definition to resolve their $ref
                $this->buildElementDefinition($tcaTypeNames);
            }
            $schema = [
                '$schema' => self::SCHEMA_DRAFT,
                'title' => $typeDefinition->getName(),
                ...$dataObject,
                '$defs' => $this->getDefinitions(),
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
        $this->elementDefinitionRequired = false;
    }

    private function getContentElementTableDefinition(): TableDefinition
    {
        return $this->tableDefinitionCollection->getTable('tt_content');
    }

    private function buildDataObject(ContentTypeInterface $typeDefinition): array
    {
        $properties = $this->buildPropertiesForColumns(
            $this->getContentElementTableDefinition(),
            $typeDefinition->getColumns(),
            $this->loadFileProcessing($typeDefinition),
            (string)$typeDefinition->getTypeName()
        );

        // Rendered child element lists declared in headless.yaml ("children"):
        // the JSON keys the site package renders container children into
        // (TypoScript "as"), typed as arrays of content block elements
        foreach ($this->headlessYamlLoader->getChildrenForContentBlock($typeDefinition->getName()) as $childKey) {
            $properties[$childKey] = [
                'type' => 'array',
                'items' => ['$ref' => '#/$defs/' . self::ELEMENT_DEFINITION_KEY],
            ];
            $this->elementDefinitionRequired = true;
        }

        // Declared JSON Schema fragments from headless.yaml ("properties"):
        // additional rendered keys not derivable from the Content Block
        // definition (sub data processors, headless.php results). Merged
        // last, so a declaration also overrides a derived mapping.
        $properties = array_merge(
            $properties,
            $this->headlessYamlLoader->getDeclaredPropertiesForContentBlock($typeDefinition->getName())
        );
        ksort($properties);

        return [
            'type' => 'object',
            'properties' => $properties,
        ];
    }

    /**
     * The discriminated union of all element branches: one per Content Block,
     * plus a loose fallback branch for every tt_content type that is
     * registered in TCA but not defined as Content Block.
     *
     * @param list<string> $tcaTypeNames
     */
    private function buildElementDefinition(array $tcaTypeNames = []): array
    {
        $branches = [];
        $contentBlockTypeNames = [];
        if ($this->tableDefinitionCollection->hasTable('tt_content')) {
            foreach ($this->getContentElementTableDefinition()->contentTypeDefinitionCollection as $typeDefinition) {
                $typeName = (string)$typeDefinition->getTypeName();
                $contentBlockTypeNames[] = $typeName;
                $definitionKey = $this->definitionKey('ctype_' . $typeName);
                $this->recordDefinitions[$definitionKey] = $this->buildDataObject($typeDefinition);
                $branches[$typeName] = $this->buildElementBranch($typeName, ['$ref' => '#/$defs/' . $definitionKey]);
            }
        }
        foreach ($this->fallbackTypeNames($tcaTypeNames, $contentBlockTypeNames) as $typeName) {
            $branches[$typeName] = $this->buildFallbackBranch($typeName);
        }
        // sorted, so generated artifacts stay byte-stable across environments
        ksort($branches);

        $elementDefinition = ['oneOf' => array_values($branches)];
        $this->recordDefinitions[self::ELEMENT_DEFINITION_KEY] = $elementDefinition;

        return $elementDefinition;
    }

    /**
     * @param array<string, mixed> $dataSchema
     * @return array<string, mixed>
     */
    private function buildElementBranch(string $typeName, array $dataSchema): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'id' => ['type' => 'integer'],
                'type' => ['const' => $typeName],
                'colPos' => ['type' => 'integer'],
                'appearance' => ['type' => 'object'],
                'data' => $dataSchema,
            ],
        ];
    }

    /**
     * Elements that are not Content Blocks (core types like "html" or
     * "shortcut", classic plugins such as "nbcontact_*") keep the generic
     * element envelope, but their data shape is not described by this schema.
     *
     * @return array<string, mixed>
     */
    private function buildFallbackBranch(string $typeName): array
    {
        return $this->buildElementBranch($typeName, ['type' => 'object'])
            + ['description' => sprintf(
                'Fallback for tt_content type "%s": not defined as Content Block, the data shape is not described by this schema.',
                $typeName
            )];
    }

    /**
     * TCA type names without a Content Block definition, without TCA's
     * internal default record type "1", sorted.
     *
     * @param list<string> $tcaTypeNames
     * @param list<string> $contentBlockTypeNames
     * @return list<string>
     */
    private function fallbackTypeNames(array $tcaTypeNames, array $contentBlockTypeNames): array
    {
        $fallbackTypeNames = array_diff($tcaTypeNames, $contentBlockTypeNames);
        $fallbackTypeNames = array_filter(
            $fallbackTypeNames,
            static fn(string $typeName): bool => $typeName !== '1'
        );
        sort($fallbackTypeNames);

        return $fallbackTypeNames;
    }

    /**
     * Declarative image processing variants from the Content Block's
     * optional headless.yaml (see HeadlessYamlLoader). TypoScript-only
     * overrides (options.processing) are not visible here — thumbnail
     * properties stay loose for those (additionalProperties).
     *
     * @return array<string, array<string, string>> field identifier => variant name => options string
     */
    private function loadFileProcessing(ContentTypeInterface $typeDefinition): array
    {
        return $this->headlessYamlLoader->getProcessingForContentBlock($typeDefinition->getName());
    }

    /**
     * @param array<string, array<string, string>> $fileProcessing
     */
    private function buildPropertiesForColumns(
        TableDefinition $tableDefinition,
        array $columns,
        array $fileProcessing = [],
        string $fileDefinitionPrefix = ''
    ): array {
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
            $properties[$field->identifier] = $this->mapField($field, $fileProcessing, $fileDefinitionPrefix);
        }
        ksort($properties);
        return $properties;
    }

    /**
     * @param array<string, array<string, string>> $fileProcessing
     */
    private function mapField(TcaFieldDefinition $field, array $fileProcessing = [], string $fileDefinitionPrefix = ''): array
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
            // A select with a foreign_table resolves to record(s) at runtime
            // (TcaPreparation::configureSelectSingle() marks selectSingle as
            // manyToOne, RelationshipType::fromTcaConfiguration() everything
            // else as list), so mirror the Relation mapping instead of the
            // static string mapping.
            $foreignTable = (string)($config['foreign_table'] ?? '');
            if ($foreignTable !== '') {
                $recordSchema = $this->recordSchemaForTable($foreignTable);
                if ((string)($config['renderType'] ?? '') === 'selectSingle' && !isset($config['MM'])) {
                    return ['anyOf' => [
                        $recordSchema,
                        ['type' => 'null'],
                    ]];
                }
                return ['type' => 'array', 'items' => $recordSchema];
            }
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
                ['$ref' => '#/$defs/linkObject'],
                ['type' => 'null'],
            ]];
        }

        if ($fieldType instanceof FileFieldType) {
            $file = $this->fileRefForField($field, $fileProcessing, $fileDefinitionPrefix);
            if (($config['relationship'] ?? '') === 'oneToOne') {
                return ['anyOf' => [
                    $file,
                    ['$ref' => '#/$defs/errorObject'],
                    ['type' => 'null'],
                ]];
            }
            return ['type' => 'array', 'items' => ['anyOf' => [
                $file,
                ['$ref' => '#/$defs/errorObject'],
            ]]];
        }

        if ($fieldType instanceof FolderFieldType) {
            return ['type' => 'array', 'items' => ['type' => 'string']];
        }

        if ($fieldType instanceof CategoryFieldType) {
            if (($config['relationship'] ?? '') === 'oneToOne') {
                return ['anyOf' => [
                    ['$ref' => '#/$defs/categoryObject'],
                    ['type' => 'null'],
                ]];
            }
            return ['type' => 'array', 'items' => ['$ref' => '#/$defs/categoryObject']];
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
            // TCA check fields are delivered as integer (0/1, or a bitmask
            // for multi-checkbox fields), never as null-typed values
            return ['type' => ['integer', 'null']];
        }

        return ['type' => 'null'];
    }

    /**
     * A $ref to the file schema of this field: a dedicated definition
     * with the concrete thumbnail variant names from headless.yaml when
     * the field declares variants, the loose shared fileObject otherwise.
     *
     * @param array<string, array<string, string>> $fileProcessing
     */
    private function fileRefForField(TcaFieldDefinition $field, array $fileProcessing, string $definitionPrefix): array
    {
        $variants = array_keys($fileProcessing[$field->identifier] ?? []);
        sort($variants);
        if ($variants === [] || $definitionPrefix === '') {
            return ['$ref' => '#/$defs/fileObject'];
        }

        $definitionKey = $this->definitionKey('file_' . $definitionPrefix . '_' . $field->identifier);
        $this->recordDefinitions[$definitionKey] = $this->buildFileObject($variants);

        return ['$ref' => '#/$defs/' . $definitionKey];
    }

    /**
     * @param list<string> $thumbnailVariantNames sorted variant names from headless.yaml
     */
    private function buildFileObject(array $thumbnailVariantNames): array
    {
        $thumbnails = ['type' => 'object'];
        if ($thumbnailVariantNames !== []) {
            $thumbnails['properties'] = array_fill_keys($thumbnailVariantNames, ['type' => 'string']);
        }
        // additionalProperties stays open: TypoScript (options.processing)
        // may add variants that headless.yaml does not declare
        $thumbnails['additionalProperties'] = ['type' => 'string'];

        return [
            'type' => 'object',
            'properties' => [
                'id' => ['type' => 'integer'],
                'alt' => ['type' => ['string', 'null']],
                'title' => ['type' => ['string', 'null']],
                'publicUrl' => ['type' => 'string'],
                'thumbnails' => $thumbnails,
            ],
        ];
    }

    private function recordSchemaForTable(string $table): array
    {
        $definitionKey = $this->definitionKey('record_' . $table);
        if (isset($this->recordDefinitionsInProgress[$definitionKey])) {
            return ['$ref' => '#/$defs/' . $definitionKey];
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

        return ['$ref' => '#/$defs/' . $definitionKey];
    }

    private function getDefinitions(): array
    {
        // sorted, so generated artifacts stay byte-stable across environments
        ksort($this->recordDefinitions);
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
            'fileObject' => $this->buildFileObject([]),
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
