<?php

declare(strict_types=1);

namespace Netzbewegung\NbHeadlessContentBlocks\DataProcessing\ToArray;

use Netzbewegung\NbHeadlessContentBlocks\Event\ModifyArrayRecursiveToArrayEvent;
use Netzbewegung\NbHeadlessContentBlocks\Normalization\Context;
use Netzbewegung\NbHeadlessContentBlocks\Normalization\Normalizer\DateTimeNormalizer;
use Netzbewegung\NbHeadlessContentBlocks\Normalization\Normalizer\FileReferenceNormalizer;
use Netzbewegung\NbHeadlessContentBlocks\Normalization\Normalizer\FlexFormNormalizer;
use Netzbewegung\NbHeadlessContentBlocks\Normalization\Normalizer\FolderCollectionNormalizer;
use Netzbewegung\NbHeadlessContentBlocks\Normalization\Normalizer\RecordCollectionNormalizer;
use Netzbewegung\NbHeadlessContentBlocks\Normalization\Normalizer\RecordNormalizer;
use Netzbewegung\NbHeadlessContentBlocks\Normalization\Normalizer\ScalarNormalizer;
use Netzbewegung\NbHeadlessContentBlocks\Normalization\Normalizer\TypolinkNormalizer;
use Netzbewegung\NbHeadlessContentBlocks\Normalization\NormalizerChain;
use Netzbewegung\NbHeadlessContentBlocks\Normalization\UnknownTypeNormalizer;
use TYPO3\CMS\ContentBlocks\Definition\TableDefinition;
use TYPO3\CMS\ContentBlocks\Definition\TableDefinitionCollection;
use TYPO3\CMS\Core\DataHandling\TableColumnType;
use TYPO3\CMS\Core\EventDispatcher\EventDispatcher;
use TYPO3\CMS\Core\Schema\Field\FieldTypeInterface;
use TYPO3\CMS\Core\Schema\TcaSchema;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;

/**
 * Phase 2 of the ToArray rewrite (docs/design/IMPROVE_TO_ARRAY.md):
 * field metadata (field type, relation targets, richtext flag, JSON
 * passthrough) now comes from the Core Schema API (TcaSchema sub-schemata),
 * ContentBlocks definitions remain solely as the source for the identifier
 * mapping (DB column -> content block field identifier).
 */
class ArrayRecursiveToArray
{
    protected ?NormalizerChain $normalizerChain = null;
    protected ?TcaSchema $tcaSchema = null;

    public function __construct(
        protected array $array,
        protected ?TableDefinition $tableDefinition,
        protected TableDefinitionCollection $tableDefinitionCollection,
        protected readonly EventDispatcher $eventDispatcher,
        protected ?string $recordType = null,
    ) {}

    /**
     * Override the TCA schema resolution (used in tests, where no
     * TcaSchemaFactory / DI container is available).
     */
    public function setTcaSchema(?TcaSchema $tcaSchema): void
    {
        $this->tcaSchema = $tcaSchema;
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

            // Dispatch event to allow custom processing
            $event = new ModifyArrayRecursiveToArrayEvent($key, $value, $tcaFieldDefinition);
            $this->eventDispatcher->dispatch($event);

            // If the event was handled by a listener, use the processed value
            if ($event->isHandled()) {
                $data[$decoratedKey] = $event->getProcessedValue();
                continue;
            }

            $data[$decoratedKey] = $this->normalizeValue($value, $key);
        }

        ksort($data);

        return $data;
    }

    protected function normalizeValue(mixed $value, int|string $key): mixed
    {
        if (is_string($value)) {
            return $this->processStringField($value, $key);
        }

        if (is_array($value) && !$this->isJsonField($key)) {
            $normalized = [];
            foreach ($value as $itemKey => $item) {
                $normalized[$itemKey] = $this->normalizeValue($item, $itemKey);
            }
            ksort($normalized);

            return $normalized;
        }

        return $this->getNormalizerChain()->normalize($value, $this->createContext());
    }

    protected function processStringField(string $value, int|string $key): string
    {
        $field = $this->getSchemaField($key);

        if ($field === null) {
            return $value;
        }

        $fieldType = TableColumnType::from($field->getType());

        if ($fieldType === TableColumnType::PASSWORD) {
            // Unclear in which case it makes sense to send a password via headless to client.
            // So we currently unset the value.
            return '';
        }

        if ($fieldType === TableColumnType::TEXT && ($field->getConfiguration()['enableRichtext'] ?? false)) {
            $contentObject = GeneralUtility::makeInstance(ContentObjectRenderer::class);
            return $contentObject->parseFunc($value, null, '< lib.parseFunc_RTE');
        }

        return $value;
    }

    protected function isJsonField(int|string $key): bool
    {
        $field = $this->getSchemaField($key);

        return $field !== null && $field->getType() === TableColumnType::JSON->value;
    }

    protected function getSchemaField(int|string $key): ?FieldTypeInterface
    {
        if (is_int($key) || !$this->getTcaSchema()?->hasField((string)$key)) {
            return null;
        }

        return $this->getTcaSchema()->getField((string)$key);
    }

    protected function getTcaSchema(): ?TcaSchema
    {
        if ($this->tcaSchema !== null) {
            return $this->tcaSchema;
        }

        $tableName = $this->tableDefinition?->table;

        if ($tableName === null) {
            return null;
        }

        $tcaSchemaFactory = $this->getTcaSchemaFactory();
        if ($tcaSchemaFactory === null || !$tcaSchemaFactory->has($tableName)) {
            return null;
        }

        $schema = $tcaSchemaFactory->get($tableName);

        if ($this->recordType !== null && $schema->hasSubSchema($this->recordType)) {
            $schema = $schema->getSubSchema($this->recordType);
        }

        return $this->tcaSchema = $schema;
    }

    protected function getNormalizerChain(): NormalizerChain
    {
        if ($this->normalizerChain === null) {
            $this->normalizerChain = new NormalizerChain(
                [
                    GeneralUtility::makeInstance(ScalarNormalizer::class),
                    GeneralUtility::makeInstance(DateTimeNormalizer::class),
                    GeneralUtility::makeInstance(FlexFormNormalizer::class),
                    GeneralUtility::makeInstance(TypolinkNormalizer::class),
                    GeneralUtility::makeInstance(RecordNormalizer::class),
                    GeneralUtility::makeInstance(RecordCollectionNormalizer::class, $this->getTcaSchemaFactory()),
                    GeneralUtility::makeInstance(FileReferenceNormalizer::class),
                    GeneralUtility::makeInstance(FolderCollectionNormalizer::class),
                ],
                GeneralUtility::makeInstance(UnknownTypeNormalizer::class)
            );
        }

        return $this->normalizerChain;
    }

    protected function getTcaSchemaFactory(): ?TcaSchemaFactory
    {
        try {
            $container = GeneralUtility::getContainer();
        } catch (\LogicException) {
            // No DI container available (unit tests).
            return null;
        }

        if (!$container->has(TcaSchemaFactory::class)) {
            return null;
        }

        return $container->get(TcaSchemaFactory::class);
    }

    protected function createContext(): Context
    {
        $context = new Context($this->getTcaSchema(), null, [], $this->eventDispatcher);
        $context->setChain($this->getNormalizerChain());

        return $context;
    }
}
