<?php

declare(strict_types=1);

namespace Netzbewegung\NbHeadlessContentBlocks\DataProcessing\ToArray;

use Exception;
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
use TYPO3\CMS\ContentBlocks\FieldType\CategoryFieldType;
use TYPO3\CMS\ContentBlocks\FieldType\ColorFieldType;
use TYPO3\CMS\ContentBlocks\FieldType\EmailFieldType;
use TYPO3\CMS\ContentBlocks\FieldType\PassFieldType;
use TYPO3\CMS\ContentBlocks\FieldType\PasswordFieldType;
use TYPO3\CMS\ContentBlocks\FieldType\SelectFieldType;
use TYPO3\CMS\ContentBlocks\FieldType\SlugFieldType;
use TYPO3\CMS\ContentBlocks\FieldType\TextareaFieldType;
use TYPO3\CMS\ContentBlocks\FieldType\TextFieldType;
use TYPO3\CMS\ContentBlocks\FieldType\UuidFieldType;
use TYPO3\CMS\Core\EventDispatcher\EventDispatcher;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;

class ArrayRecursiveToArray
{
    protected ?NormalizerChain $normalizerChain = null;

    public function __construct(
        protected array $array,
        protected ?TableDefinition $tableDefinition,
        protected TableDefinitionCollection $tableDefinitionCollection,
        protected readonly EventDispatcher $eventDispatcher
    ) {}

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

            switch (true) {
                case is_string($value):
                    $data[$decoratedKey] = $this->processStringField($value, $key);
                    break;
                default:
                    $data[$decoratedKey] = $this->getNormalizerChain()->normalize($value, $this->createContext());
            }
        }

        ksort($data);

        return $data;
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
            // makeInstance() resolves TcaSchemaFactory from the DI container
            // whenever one is available. Without a container (unit tests) the
            // class cannot be constructed manually, hence the nullable result.
            // Removed when the chain is DI-wired in a later phase.
            return GeneralUtility::makeInstance(TcaSchemaFactory::class);
        } catch (\ArgumentCountError) {
            return null;
        }
    }

    protected function createContext(): Context
    {
        $context = new Context(null, null, [], $this->eventDispatcher);
        $context->setChain($this->getNormalizerChain());

        return $context;
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
                //debug($fieldType);
                //throw new Exception('Unknown default case in ->processStringField() for key "' . $key . '"', 1746095966);
        }

        return $value;
    }
}
