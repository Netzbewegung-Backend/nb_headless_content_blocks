<?php

declare(strict_types=1);

namespace Netzbewegung\NbHeadlessContentBlocks\Tests\Unit\DataProcessing\ToArray;

use Netzbewegung\NbHeadlessContentBlocks\DataProcessing\ToArray\LazyRecordCollectionSysCategoryToArray;
use TYPO3\CMS\Core\Collection\LazyRecordCollection;
use TYPO3\CMS\Core\Domain\RecordInterface;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class LazyRecordCollectionSysCategoryToArrayTest extends UnitTestCase
{
    public function testToArrayExtractsOnlyUidPidTitle(): void
    {
        $records = [
            $this->createRecord(['uid' => 1, 'pid' => 0, 'title' => 'First', 'description' => 'Should be removed']),
            $this->createRecord(['uid' => 2, 'pid' => 1, 'title' => 'Second', 'description' => 'Also removed']),
        ];

        $collection = new LazyRecordCollection('test', fn() => $records);
        $subject = new LazyRecordCollectionSysCategoryToArray($collection);

        self::assertSame([
            0 => ['uid' => 1, 'pid' => 0, 'title' => 'First'],
            1 => ['uid' => 2, 'pid' => 1, 'title' => 'Second'],
        ], $subject->toArray());
    }

    public function testToArrayReturnsEmptyArrayForEmptyCollection(): void
    {
        $collection = new LazyRecordCollection('test', fn() => []);
        $subject = new LazyRecordCollectionSysCategoryToArray($collection);

        self::assertSame([], $subject->toArray());
    }

    private function createRecord(array $properties): RecordInterface
    {
        $record = $this->createMock(RecordInterface::class);
        $record->method('toArray')->willReturn($properties);

        return $record;
    }
}
