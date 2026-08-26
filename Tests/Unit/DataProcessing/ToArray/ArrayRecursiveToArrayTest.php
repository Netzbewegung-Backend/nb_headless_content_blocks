<?php

declare(strict_types=1);

namespace Netzbewegung\NbHeadlessContentBlocks\Tests\Unit\DataProcessing\ToArray;

use Netzbewegung\NbHeadlessContentBlocks\DataProcessing\ToArray\ArrayRecursiveToArray;
use Netzbewegung\NbHeadlessContentBlocks\Event\ModifyArrayRecursiveToArrayEvent;
use Psr\EventDispatcher\ListenerProviderInterface;
use TYPO3\CMS\ContentBlocks\Definition\Capability\TableDefinitionCapability;
use TYPO3\CMS\ContentBlocks\Definition\ContentType\ContentType;
use TYPO3\CMS\ContentBlocks\Definition\ContentType\ContentTypeDefinitionCollection;
use TYPO3\CMS\ContentBlocks\Definition\PaletteDefinitionCollection;
use TYPO3\CMS\ContentBlocks\Definition\SqlColumnDefinitionCollection;
use TYPO3\CMS\ContentBlocks\Definition\TableDefinition;
use TYPO3\CMS\ContentBlocks\Definition\TableDefinitionCollection;
use TYPO3\CMS\ContentBlocks\Definition\TcaFieldDefinition;
use TYPO3\CMS\ContentBlocks\FieldType\CategoryFieldType;
use TYPO3\CMS\ContentBlocks\FieldType\FieldTypeInterface;
use TYPO3\CMS\ContentBlocks\FieldType\PasswordFieldType;
use TYPO3\CMS\ContentBlocks\FieldType\RelationFieldType;
use TYPO3\CMS\ContentBlocks\Registry\AutomaticLanguageKeysRegistry;
use TYPO3\CMS\Core\Collection\LazyRecordCollection;
use TYPO3\CMS\Core\Domain\FlexFormFieldValues;
use TYPO3\CMS\Core\Domain\Record;
use TYPO3\CMS\Core\EventDispatcher\EventDispatcher;
use TYPO3\CMS\Core\Resource\Collection\LazyFolderCollection;
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\Resource\ResourceStorage;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class ArrayRecursiveToArrayTest extends UnitTestCase
{
    public function testNullValueIsPassedThrough(): void
    {
        $subject = $this->createSubject(['key' => null]);

        self::assertSame(['key' => null], $subject->toArray());
    }

    public function testIntegerValueIsPassedThrough(): void
    {
        $subject = $this->createSubject(['key' => 42]);

        self::assertSame(['key' => 42], $subject->toArray());
    }

    public function testStringValueIsPassedThrough(): void
    {
        $subject = $this->createSubject(['key' => 'value']);

        self::assertSame(['key' => 'value'], $subject->toArray());
    }

    public function testBooleanValueIsNormalizedToNull(): void
    {
        $subject = $this->createSubject(['key' => true]);

        self::assertSame(['key' => null], $subject->toArray());
    }

    public function testFloatValueIsNormalizedToNull(): void
    {
        $subject = $this->createSubject(['key' => 13.37]);

        self::assertSame(['key' => null], $subject->toArray());
    }

    public function testDateTimeImmutableIsFormattedAsW3C(): void
    {
        $dateTime = new \DateTimeImmutable('2026-07-22 10:15:30', new \DateTimeZone('UTC'));

        $subject = $this->createSubject(['key' => $dateTime]);

        self::assertSame(['key' => $dateTime->format(\DateTimeImmutable::W3C)], $subject->toArray());
    }

    public function testNestedArrayIsProcessedRecursively(): void
    {
        $subject = $this->createSubject([
            'level1' => [
                'level2' => [
                    'key' => 'value',
                ],
            ],
        ]);

        self::assertSame([
            'level1' => [
                'level2' => [
                    'key' => 'value',
                ],
            ],
        ], $subject->toArray());
    }

    public function testResultIsSortedByKey(): void
    {
        $subject = $this->createSubject([
            'zulu' => 1,
            'alpha' => 2,
            'mike' => 3,
        ]);

        self::assertSame([
            'alpha' => 2,
            'mike' => 3,
            'zulu' => 1,
        ], $subject->toArray());
    }

    public function testHandledEventProcessedValueIsUsed(): void
    {
        $listener = static function (ModifyArrayRecursiveToArrayEvent $event): void {
            $event->setProcessedValue('processed');
        };

        $subject = $this->createSubject(['key' => 'original'], [$listener]);

        self::assertSame(['key' => 'processed'], $subject->toArray());
    }

    public function testEventReceivesKeyAndValue(): void
    {
        $receivedEvents = [];
        $listener = static function (ModifyArrayRecursiveToArrayEvent $event) use (&$receivedEvents): void {
            $receivedEvents[] = [$event->getKey(), $event->getValue()];
        };

        $subject = $this->createSubject(['myKey' => 'myValue'], [$listener]);
        $subject->toArray();

        self::assertSame([['myKey', 'myValue']], $receivedEvents);
    }

    public function testPasswordFieldIsEmptied(): void
    {
        $tableDefinition = $this->createTableDefinition(['secret_password' => new PasswordFieldType()]);
        $subject = $this->createSubjectWithTableDefinition(
            ['secret_password' => 'mySecretValue'],
            $tableDefinition
        );

        self::assertSame(['secret_password' => ''], $subject->toArray());
    }

    public function testStringWithIntKeyIsPassedThrough(): void
    {
        $tableDefinition = $this->createTableDefinition(['myField' => new PasswordFieldType()]);
        $subject = $this->createSubjectWithTableDefinition(
            ['keep' => 'this string'],
            $tableDefinition
        );

        self::assertSame(['keep' => 'this string'], $subject->toArray());
    }

    public function testStringWithNoTableDefinitionIsPassedThrough(): void
    {
        $subject = $this->createSubject(['key' => 'hello']);

        self::assertSame(['key' => 'hello'], $subject->toArray());
    }

    public function testRecordValueUnderTableNameKeyIsConvertedViaRecordToArray(): void
    {
        $record = $this->createMock(Record::class);
        $record->method('toArray')->willReturn(['uid' => 7, 'title' => 'Record title']);

        $tableDefinitionCollection = new TableDefinitionCollection(new AutomaticLanguageKeysRegistry());
        $tableDefinitionCollection->addTable($this->createTableDefinition([], 'tx_test_table'));

        $subject = $this->createSubjectWithCollection(['tx_test_table' => $record], $tableDefinitionCollection);

        self::assertSame(['tx_test_table' => ['title' => 'Record title']], $subject->toArray());
    }

    public function testRecordValueUnderUnknownKeyIsConvertedWithoutTableDefinition(): void
    {
        $record = $this->createMock(Record::class);
        $record->method('toArray')->willReturn(['uid' => 1, 'bodytext' => 'Hello']);

        $subject = $this->createSubject(['some_key' => $record]);

        self::assertSame(['some_key' => ['bodytext' => 'Hello']], $subject->toArray());
    }

    public function testFlexFormFieldValuesAreConvertedToRawArray(): void
    {
        $flexFormFieldValues = new FlexFormFieldValues([
            'sDEF' => ['settings' => ['value' => 'flex']],
        ]);

        $subject = $this->createSubject(['pi_flexform' => $flexFormFieldValues]);

        self::assertSame(
            ['pi_flexform' => ['sDEF' => ['settings' => ['value' => 'flex']]]],
            $subject->toArray()
        );
    }

    public function testLazyFolderCollectionValueIsConvertedToPaths(): void
    {
        $storage = $this->createMock(ResourceStorage::class);
        $storage->method('getConfiguration')->willReturn(['basePath' => '/files/']);

        $folder = $this->createMock(Folder::class);
        $folder->method('getStorage')->willReturn($storage);
        $folder->method('getIdentifier')->willReturn('/documents/report.pdf');

        $collection = new LazyFolderCollection('test', fn() => [$folder]);

        $subject = $this->createSubject(['my_folders' => $collection]);

        self::assertSame(['my_folders' => ['//files/documents/report.pdf']], $subject->toArray());
    }

    public function testCategoryFieldCollectionIsReducedToSysCategoryArray(): void
    {
        $record = $this->createMock(Record::class);
        $record->method('getRawRecord')->willReturn(
            new \TYPO3\CMS\Core\Domain\RawRecord(3, 2, [], new \TYPO3\CMS\Core\Domain\Record\ComputedProperties(), 'sys_category')
        );
        $record->method('toArray')->willReturn(['uid' => 3, 'pid' => 2, 'title' => 'Category title']);

        $collection = new LazyRecordCollection('my_categories', fn() => [$record]);

        $tableDefinition = $this->createTableDefinition([
            'my_categories' => (new CategoryFieldType())->createFromArray([]),
        ]);
        $subject = $this->createSubjectWithTableDefinition(['my_categories' => $collection], $tableDefinition);

        self::assertSame(
            ['my_categories' => [['uid' => 3, 'pid' => 2, 'title' => 'Category title']]],
            $subject->toArray()
        );
    }

    public function testRelationFieldWithForeignTableResolvesTableDefinitionForRecordValue(): void
    {
        $record = $this->createMock(Record::class);
        $record->method('toArray')->willReturn(['uid' => 9, 'text' => 'Related item']);

        $tableDefinitionCollection = new TableDefinitionCollection(new AutomaticLanguageKeysRegistry());
        $tableDefinitionCollection->addTable($this->createTableDefinition([], 'tx_known_table'));

        $tableDefinition = $this->createTableDefinition([
            'my_relation' => $this->createRelationFieldType(['foreign_table' => 'tx_known_table']),
        ]);

        $subject = $this->createSubjectWithCollection(['my_relation' => $record], $tableDefinitionCollection, $tableDefinition);

        self::assertSame(['my_relation' => ['text' => 'Related item']], $subject->toArray());
    }

    public function testRelationFieldWithForeignTableResolvesTableDefinitionForCollectionValue(): void
    {
        $record = $this->createMock(Record::class);
        $record->method('toArray')->willReturn(['uid' => 2, 'text' => 'Collection item']);

        $collection = new LazyRecordCollection('my_relation', fn() => [$record]);

        $tableDefinitionCollection = new TableDefinitionCollection(new AutomaticLanguageKeysRegistry());
        $tableDefinitionCollection->addTable($this->createTableDefinition([], 'tx_known_table'));

        $tableDefinition = $this->createTableDefinition([
            'my_relation' => $this->createRelationFieldType(['foreign_table' => 'tx_known_table']),
        ]);

        $subject = $this->createSubjectWithCollection(['my_relation' => $collection], $tableDefinitionCollection, $tableDefinition);

        self::assertSame(['my_relation' => [['text' => 'Collection item']]], $subject->toArray());
    }

    public function testRelationFieldWithSingleAllowedTableResolvesTableName(): void
    {
        $record = $this->createMock(Record::class);
        $record->method('toArray')->willReturn(['uid' => 4, 'bodytext' => 'Allowed item']);

        $tableDefinition = $this->createTableDefinition([
            'my_group' => $this->createRelationFieldType(['allowed' => 'tx_allowed_single']),
        ]);

        $subject = $this->createSubjectWithTableDefinition(['my_group' => $record], $tableDefinition);

        self::assertSame(['my_group' => ['bodytext' => 'Allowed item']], $subject->toArray());
    }

    public function testRelationFieldWithMultipleAllowedTablesGetsNoTableDefinition(): void
    {
        $record = $this->createMock(Record::class);
        $record->method('toArray')->willReturn(['uid' => 5, 'bodytext' => 'Multi item']);

        $tableDefinition = $this->createTableDefinition([
            'my_multi' => $this->createRelationFieldType(['allowed' => 'tx_a,tx_b']),
        ]);

        $subject = $this->createSubjectWithTableDefinition(['my_multi' => $record], $tableDefinition);

        self::assertSame(['my_multi' => ['bodytext' => 'Multi item']], $subject->toArray());
    }

    /**
     * @param array<string, mixed> $array
     * @param callable[] $listeners
     */
    private function createSubject(array $array, array $listeners = []): ArrayRecursiveToArray
    {
        return $this->createSubjectWithCollection($array, new TableDefinitionCollection(new AutomaticLanguageKeysRegistry()), null, $listeners);
    }

    /**
     * @param callable[] $listeners
     */
    private function createEventDispatcher(array $listeners): EventDispatcher
    {
        $listenerProvider = new class ($listeners) implements ListenerProviderInterface {
            public function __construct(private readonly array $listeners) {}

            public function getListenersForEvent(object $event): iterable
            {
                return $this->listeners;
            }
        };

        return new EventDispatcher($listenerProvider);
    }

    /**
     * @param array<string, mixed> $array
     * @param callable[] $listeners
     */
    private function createSubjectWithTableDefinition(array $array, TableDefinition $tableDefinition, array $listeners = []): ArrayRecursiveToArray
    {
        return $this->createSubjectWithCollection($array, new TableDefinitionCollection(new AutomaticLanguageKeysRegistry()), $tableDefinition, $listeners);
    }

    /**
     * @param array<string, mixed> $array
     * @param callable[] $listeners
     */
    private function createSubjectWithCollection(array $array, TableDefinitionCollection $tableDefinitionCollection, ?TableDefinition $tableDefinition = null, array $listeners = []): ArrayRecursiveToArray
    {
        return new ArrayRecursiveToArray(
            $array,
            $tableDefinition,
            $tableDefinitionCollection,
            $this->createEventDispatcher($listeners)
        );
    }

    /**
     * @param array<string, mixed> $settings
     */
    private function createRelationFieldType(array $settings): RelationFieldType
    {
        $reflection = new \ReflectionClass(RelationFieldType::class);
        $attribute = $reflection->getAttributes(\TYPO3\CMS\ContentBlocks\FieldType\FieldType::class)[0]->newInstance();

        $fieldType = new RelationFieldType();
        $fieldType->setName($attribute->name);
        $fieldType->setTcaType($attribute->tcaType);

        return $fieldType->createFromArray($settings);
    }

    /**
     * @param array<string, FieldTypeInterface> $fields
     */
    private function createTableDefinition(array $fields, string $table = 'tt_content'): TableDefinition
    {
        $tcaFieldDefinitionCollection = \TYPO3\CMS\ContentBlocks\Definition\TcaFieldDefinitionCollection::createFromArray([], $table);

        foreach ($fields as $identifier => $fieldType) {
            $args = [
                'parentContentType' => ContentType::CONTENT_ELEMENT,
                'identifier' => $identifier,
                'uniqueIdentifier' => $identifier,
                'labelPath' => '',
                'descriptionPath' => '',
                'placeholderPath' => '',
                'useExistingField' => false,
                'fieldType' => $fieldType,
            ];
            if (property_exists(TcaFieldDefinition::class, 'parentTable')) {
                $args['parentTable'] = $table;
            }
            $tcaFieldDefinitionCollection->addField(new TcaFieldDefinition(...$args));
        }

        return new TableDefinition(
            table: $table,
            capability: TableDefinitionCapability::createFromArray([]),
            typeField: 'CType',
            contentType: ContentType::CONTENT_ELEMENT,
            contentTypeDefinitionCollection: ContentTypeDefinitionCollection::createFromArray([], $table),
            sqlColumnDefinitionCollection: SqlColumnDefinitionCollection::createFromArray([], $table),
            tcaFieldDefinitionCollection: $tcaFieldDefinitionCollection,
            paletteDefinitionCollection: PaletteDefinitionCollection::createFromArray([], $table),
            parentReferences: []
        );
    }
}
