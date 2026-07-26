<?php

declare(strict_types=1);

namespace Netzbewegung\NbHeadlessContentBlocks\Tests\Unit\DataProcessing\ToArray;

use Netzbewegung\NbHeadlessContentBlocks\DataProcessing\ToArray\LazyRecordCollectionToArray;
use TYPO3\CMS\ContentBlocks\Definition\TableDefinitionCollection;
use TYPO3\CMS\ContentBlocks\Registry\AutomaticLanguageKeysRegistry;
use TYPO3\CMS\Core\Collection\LazyRecordCollection;
use TYPO3\CMS\Core\Domain\RawRecord;
use TYPO3\CMS\Core\Domain\Record\ComputedProperties;
use TYPO3\CMS\Core\Domain\RecordInterface;
use TYPO3\CMS\Core\EventDispatcher\EventDispatcher;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class LazyRecordCollectionToArrayTest extends UnitTestCase
{
    public function testToArrayThrowsExceptionForUnknownTable(): void
    {
        $rawRecord = $this->createRawRecord('unknown_table');

        $record = $this->createMock(RecordInterface::class);
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

    /**
     * @return RawRecord
     */
    private function createRawRecord(string $table): RawRecord
    {
        return new RawRecord(1, 0, [], new ComputedProperties(), $table);
    }
}
