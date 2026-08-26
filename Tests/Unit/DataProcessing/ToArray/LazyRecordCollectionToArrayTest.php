<?php

declare(strict_types=1);

namespace Netzbewegung\NbHeadlessContentBlocks\Tests\Unit\DataProcessing\ToArray;

use Netzbewegung\NbHeadlessContentBlocks\DataProcessing\ToArray\LazyRecordCollectionToArray;
use TYPO3\CMS\ContentBlocks\Definition\Capability\TableDefinitionCapability;
use TYPO3\CMS\ContentBlocks\Definition\ContentType\ContentType;
use TYPO3\CMS\ContentBlocks\Definition\ContentType\ContentTypeDefinitionCollection;
use TYPO3\CMS\ContentBlocks\Definition\PaletteDefinitionCollection;
use TYPO3\CMS\ContentBlocks\Definition\SqlColumnDefinitionCollection;
use TYPO3\CMS\ContentBlocks\Definition\TableDefinition;
use TYPO3\CMS\ContentBlocks\Definition\TableDefinitionCollection;
use TYPO3\CMS\ContentBlocks\Definition\TcaFieldDefinition;
use TYPO3\CMS\ContentBlocks\FieldType\TextFieldType;
use TYPO3\CMS\ContentBlocks\Registry\AutomaticLanguageKeysRegistry;
use TYPO3\CMS\Core\Collection\LazyRecordCollection;
use TYPO3\CMS\Core\Domain\RawRecord;
use TYPO3\CMS\Core\Domain\Record;
use TYPO3\CMS\Core\Domain\Record\ComputedProperties;
use TYPO3\CMS\Core\EventDispatcher\EventDispatcher;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class LazyRecordCollectionToArrayTest extends UnitTestCase
{
    public function testToArrayThrowsExceptionForUnknownTable(): void
    {
        $rawRecord = $this->createRawRecord('unknown_table');

        $record = $this->createMock(Record::class);
        $record->method('getRawRecord')->willReturn($rawRecord);

        $collection = new LazyRecordCollection('test', fn() => [$record]);
        $tableDefinitionCollection = new TableDefinitionCollection(new AutomaticLanguageKeysRegistry());
        $eventDispatcher = $this->createMock(EventDispatcher::class);

        $subject = new LazyRecordCollectionToArray($collection, null, $tableDefinitionCollection, $eventDispatcher);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Unknown case in LazyRecordCollectionToArray->toArray() switch for key "0"');
        $this->expectExceptionCode(1746095968);

        $subject->toArray();
    }

    public function testToArrayConvertsSysCategoryRecordsWithNullTableDefinition(): void
    {
        $record = $this->createMock(Record::class);
        $record->method('getRawRecord')->willReturn($this->createRawRecord('sys_category'));
        $record->method('toArray')->willReturn(['uid' => 3, 'pid' => 2, 'title' => 'Category title']);

        $collection = new LazyRecordCollection('test', fn() => [$record]);
        $tableDefinitionCollection = new TableDefinitionCollection(new AutomaticLanguageKeysRegistry());
        $eventDispatcher = $this->createMock(EventDispatcher::class);

        $subject = new LazyRecordCollectionToArray($collection, null, $tableDefinitionCollection, $eventDispatcher);

        self::assertSame([['title' => 'Category title']], $subject->toArray());
    }

    public function testToArrayUsesResolvedTableDefinitionForKnownTable(): void
    {
        $record = $this->createMock(Record::class);
        $record->method('getRawRecord')->willReturn($this->createRawRecord('tt_content'));
        $record->method('toArray')->willReturn(['uid' => 5, 'bodytext' => 'Known table content']);

        $collection = new LazyRecordCollection('test', fn() => [$record]);

        $tableDefinitionCollection = new TableDefinitionCollection(new AutomaticLanguageKeysRegistry());
        $tableDefinitionCollection->addTable($this->createTableDefinition('tt_content'));
        $eventDispatcher = $this->createMock(EventDispatcher::class);

        $subject = new LazyRecordCollectionToArray($collection, null, $tableDefinitionCollection, $eventDispatcher);

        self::assertSame([['bodytext' => 'Known table content']], $subject->toArray());
    }

    private function createRawRecord(string $table): RawRecord
    {
        return new RawRecord(1, 0, [], new ComputedProperties(), $table);
    }

    private function createTableDefinition(string $table): TableDefinition
    {
        $args = [
            'parentContentType' => ContentType::CONTENT_ELEMENT,
            'identifier' => 'bodytext',
            'uniqueIdentifier' => 'bodytext',
            'labelPath' => '',
            'descriptionPath' => '',
            'placeholderPath' => '',
            'useExistingField' => false,
            'fieldType' => new TextFieldType(),
        ];
        if (property_exists(TcaFieldDefinition::class, 'parentTable')) {
            $args['parentTable'] = $table;
        }
        $tcaFieldDefinition = new TcaFieldDefinition(...$args);

        $tcaFieldDefinitionCollection = \TYPO3\CMS\ContentBlocks\Definition\TcaFieldDefinitionCollection::createFromArray([], $table);
        $tcaFieldDefinitionCollection->addField($tcaFieldDefinition);

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
