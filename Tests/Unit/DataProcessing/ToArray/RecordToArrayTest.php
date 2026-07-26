<?php

declare(strict_types=1);

namespace Netzbewegung\NbHeadlessContentBlocks\Tests\Unit\DataProcessing\ToArray;

use Netzbewegung\NbHeadlessContentBlocks\DataProcessing\ToArray\RecordToArray;
use TYPO3\CMS\ContentBlocks\Definition\TableDefinitionCollection;
use TYPO3\CMS\ContentBlocks\Registry\AutomaticLanguageKeysRegistry;
use TYPO3\CMS\Core\Domain\Record;
use TYPO3\CMS\Core\EventDispatcher\EventDispatcher;
use TYPO3\CMS\Core\Resource\Exception\FileDoesNotExistException;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class RecordToArrayTest extends UnitTestCase
{
    public function testToArrayRemovesSystemFields(): void
    {
        $record = $this->createMock(Record::class);
        $record->method('toArray')->willReturn([
            'uid' => 1,
            'pid' => 0,
            'colPos' => 0,
            'CType' => 'text',
            'foreign_table_parent_uid' => 0,
            'tx_container_parent' => 0,
            'bodytext' => 'Hello World',
        ]);

        $tableDefinitionCollection = new TableDefinitionCollection(new AutomaticLanguageKeysRegistry());
        $eventDispatcher = $this->createMock(EventDispatcher::class);

        $subject = new RecordToArray($record, null, $tableDefinitionCollection, $eventDispatcher);
        $result = $subject->toArray();

        self::assertArrayNotHasKey('uid', $result);
        self::assertArrayNotHasKey('pid', $result);
        self::assertArrayNotHasKey('colPos', $result);
        self::assertArrayNotHasKey('CType', $result);
        self::assertArrayNotHasKey('foreign_table_parent_uid', $result);
        self::assertArrayNotHasKey('tx_container_parent', $result);
        self::assertSame('Hello World', $result['bodytext']);
    }

    public function testToArrayReturnsErrorMessageOnFileDoesNotExistException(): void
    {
        $record = $this->createMock(Record::class);
        $record->method('toArray')->willThrowException(new FileDoesNotExistException('File not found'));

        $tableDefinitionCollection = new TableDefinitionCollection(new AutomaticLanguageKeysRegistry());
        $eventDispatcher = $this->createMock(EventDispatcher::class);

        $subject = new RecordToArray($record, null, $tableDefinitionCollection, $eventDispatcher);
        $result = $subject->toArray();

        self::assertArrayHasKey('__errorMessage', $result);
        self::assertSame('File not found', $result['__errorMessage']);
    }
}
