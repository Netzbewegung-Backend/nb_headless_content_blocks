<?php

declare(strict_types=1);

namespace Netzbewegung\NbHeadlessContentBlocks\Normalization;

use Netzbewegung\NbHeadlessContentBlocks\ContentBlocks\HeadlessYamlLoader;
use Netzbewegung\NbHeadlessContentBlocks\ContentBlocks\IdentifierMapperInterface;
use Netzbewegung\NbHeadlessContentBlocks\Event\ModifyArrayRecursiveToArrayEvent;
use Netzbewegung\NbHeadlessContentBlocks\FieldTransformer\FieldValueTransformerChain;
use TYPO3\CMS\ContentBlocks\Definition\TableDefinitionCollection;
use TYPO3\CMS\ContentBlocks\Registry\ContentBlockRegistry;
use TYPO3\CMS\Core\Domain\Record;
use TYPO3\CMS\Core\Domain\RecordInterface;
use TYPO3\CMS\Core\EventDispatcher\EventDispatcher;
use TYPO3\CMS\Core\Resource\Exception\FileDoesNotExistException;
use TYPO3\CMS\Core\Schema\Field\FieldTypeInterface;
use TYPO3\CMS\Core\Schema\TcaSchema;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;

/**
 * DI-wired entry point of the ToArray conversion (Phase 4): converts a
 * resolved Record into the JSON-compatible array, using the NormalizerChain,
 * the FieldValueTransformerChain and the IdentifierMapper. Replaces the
 * former RecordToArray/ArrayRecursiveToArray assembly.
 */
final class RecordArrayBuilder
{
    private const SYSTEM_FIELDS = ['uid', 'pid', 'colPos', 'CType', 'foreign_table_parent_uid', 'tx_container_parent'];

    public function __construct(
        private readonly NormalizerChain $normalizerChain,
        private readonly FieldValueTransformerChain $fieldValueTransformerChain,
        private readonly IdentifierMapperInterface $identifierMapper,
        private readonly TableDefinitionCollection $tableDefinitionCollection,
        private readonly TcaSchemaFactory $tcaSchemaFactory,
        private readonly HeadlessYamlLoader $headlessYamlLoader,
        private readonly EventDispatcher $eventDispatcher,
        private readonly ?ContentBlockRegistry $contentBlockRegistry = null,
    ) {}

    /**
     * @param array<string, mixed> $typoScriptOptions processor "options." (TypoScript)
     * @return array<string, mixed>
     */
    public function build(RecordInterface $record, array $typoScriptOptions = []): array
    {
        try {
            $array = $record->toArray();
        } catch (FileDoesNotExistException $fileDoesNotExistException) {
            return ['__errorMessage' => $fileDoesNotExistException->getMessage()];
        }

        foreach (self::SYSTEM_FIELDS as $systemField) {
            unset($array[$systemField]);
        }

        $table = $record instanceof Record ? $record->getMainType() : $record->getRawRecord()->getMainType();
        $recordType = $record->getRecordType();
        $tcaSchema = $this->resolveTcaSchema($table, $recordType);
        $fileProcessing = $this->resolveFileProcessing($table, $recordType, $typoScriptOptions);

        $data = [];
        foreach ($array as $key => $value) {
            $decoratedKey = $this->identifierMapper->mapColumnToIdentifier($table, $recordType, (string)$key) ?? $key;

            // Dispatch event to allow custom processing (deprecated, kept for
            // backwards compatibility until the next minor release).
            $tcaFieldDefinition = null;
            if (
                $this->tableDefinitionCollection->hasTable($table)
                && is_string($key)
            ) {
                $tableDefinition = $this->tableDefinitionCollection->getTable($table);
                if ($tableDefinition->tcaFieldDefinitionCollection->hasField($key)) {
                    $tcaFieldDefinition = $tableDefinition->tcaFieldDefinitionCollection->getField($key);
                }
            }

            $event = new ModifyArrayRecursiveToArrayEvent($key, $value, $tcaFieldDefinition);
            $this->eventDispatcher->dispatch($event);

            if ($event->isHandled()) {
                $data[$decoratedKey] = $event->getProcessedValue();
                continue;
            }

            $data[$decoratedKey] = $this->normalizeValue(
                $value,
                $key,
                $decoratedKey,
                $tcaSchema,
                $fileProcessing,
                $typoScriptOptions
            );
        }

        ksort($data);

        return $data;
    }

    private function normalizeValue(
        mixed $value,
        int|string $key,
        ?string $fieldIdentifier,
        ?TcaSchema $tcaSchema,
        array $fileProcessing,
        array $typoScriptOptions,
    ): mixed {
        if (is_string($value)) {
            return $this->transformStringValue($value, $key, $tcaSchema);
        }

        if (is_array($value) && !$this->isJsonField($key, $tcaSchema)) {
            $normalized = [];
            foreach ($value as $itemKey => $item) {
                $normalized[$itemKey] = $this->normalizeValue($item, $itemKey, $fieldIdentifier, $tcaSchema, $fileProcessing, $typoScriptOptions);
            }
            ksort($normalized);

            return $normalized;
        }

        $context = $this->createContext($tcaSchema, $fileProcessing, $typoScriptOptions);
        if ($fieldIdentifier !== null) {
            $context = $context->withCurrentFieldIdentifier($fieldIdentifier);
        }

        return $this->normalizerChain->normalize($value, $context);
    }

    private function transformStringValue(string $value, int|string $key, ?TcaSchema $tcaSchema): string
    {
        $field = $this->getSchemaField($key, $tcaSchema);

        if ($field === null) {
            return $value;
        }

        return $this->fieldValueTransformerChain->transform($value, $field);
    }

    private function isJsonField(int|string $key, ?TcaSchema $tcaSchema): bool
    {
        $field = $this->getSchemaField($key, $tcaSchema);

        return $field !== null && $field->getType() === 'json';
    }

    private function getSchemaField(int|string $key, ?TcaSchema $tcaSchema): ?FieldTypeInterface
    {
        if (is_int($key) || $tcaSchema === null || !$tcaSchema->hasField((string)$key)) {
            return null;
        }

        return $tcaSchema->getField((string)$key);
    }

    private function resolveTcaSchema(string $table, ?string $recordType): ?TcaSchema
    {
        if (!$this->tcaSchemaFactory->has($table)) {
            return null;
        }

        $schema = $this->tcaSchemaFactory->get($table);
        if ($recordType !== null && $schema->hasSubSchema($recordType)) {
            $schema = $schema->getSubSchema($recordType);
        }

        return $schema;
    }

    /**
     * @return array<string, array<string, string>> field identifier => variant name => options string
     */
    private function resolveFileProcessing(string $table, ?string $recordType, array $typoScriptOptions): array
    {
        $processing = [];

        $contentBlockName = null;
        if ($recordType !== null && $this->contentBlockRegistry !== null) {
            $contentBlockName = $this->contentBlockRegistry
                ->getByTypeName($table, $recordType)?->getName();
        }
        if ($contentBlockName !== null) {
            $processing = $this->headlessYamlLoader->getProcessingForContentBlock($contentBlockName);
        }

        // TypoScript hands over keys with the trailing dot ("processing.",
        // "my_image."); accept both spellings. Variants merge per name:
        // TypoScript wins over headless.yaml, headless.yaml variants stay.
        $typoScriptOverride = $typoScriptOptions['processing.'] ?? $typoScriptOptions['processing'] ?? [];
        if (is_array($typoScriptOverride)) {
            foreach ($typoScriptOverride as $fieldIdentifier => $variants) {
                if (is_array($variants)) {
                    $identifier = rtrim((string)$fieldIdentifier, '.');
                    $processing[$identifier] = array_map(
                        static fn(mixed $value): string => (string)$value,
                        $variants
                    ) + ($processing[$identifier] ?? []);
                }
            }
        }

        return $processing;
    }

    private function createContext(?TcaSchema $tcaSchema, array $fileProcessing, array $typoScriptOptions): Context
    {
        $context = new Context(
            $tcaSchema,
            null,
            $typoScriptOptions,
            $this->eventDispatcher,
            $fileProcessing
        );
        $context->setChain($this->normalizerChain);
        $context->setRecordBuilder(fn(RecordInterface $record): array => $this->build($record));

        return $context;
    }
}
