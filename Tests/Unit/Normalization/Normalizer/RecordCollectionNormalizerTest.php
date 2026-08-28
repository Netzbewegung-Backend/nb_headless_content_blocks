<?php

declare(strict_types=1);

namespace Netzbewegung\NbHeadlessContentBlocks\Tests\Unit\Normalization\Normalizer;

use Netzbewegung\NbHeadlessContentBlocks\Normalization\Context;
use Netzbewegung\NbHeadlessContentBlocks\Normalization\Normalizer\RecordCollectionNormalizer;
use Netzbewegung\NbHeadlessContentBlocks\Normalization\NormalizerChain;
use Netzbewegung\NbHeadlessContentBlocks\Normalization\NormalizerInterface;
use Netzbewegung\NbHeadlessContentBlocks\Normalization\UnknownTypeNormalizer;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\EventDispatcher\EventDispatcherInterface;
use TYPO3\CMS\Core\Collection\LazyRecordCollection;
use TYPO3\CMS\Core\Domain\RawRecord;
use TYPO3\CMS\Core\Domain\RecordInterface;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class RecordCollectionNormalizerTest extends UnitTestCase
{
    #[Test]
    public function normalizesRecordsOfTablesWithoutSchemaThroughTheChain(): void
    {
        $record = $this->createRecord('some_unknown_table');
        $collection = $this->createCollection([$record]);

        $schemaFactory = $this->createSchemaFactory(hasSchema: false);
        $innerNormalizer = $this->createMock(NormalizerInterface::class);
        $innerNormalizer->method('supports')->willReturn(true);
        $innerNormalizer->method('normalize')->willReturn('normalized');

        $context = new Context(null, null, [], self::createStub(EventDispatcherInterface::class));
        $context->setChain(new NormalizerChain([$innerNormalizer], new UnknownTypeNormalizer()));
        $context->setRecordBuilder(static fn(): array => []);

        $subject = new RecordCollectionNormalizer($schemaFactory);

        self::assertSame(['normalized'], $subject->normalize($collection, $context));
    }

    private function createRecord(string $mainType): RecordInterface&MockObject
    {
        $rawRecord = $this->createMock(RawRecord::class);
        $rawRecord->method('getMainType')->willReturn($mainType);

        $record = $this->createMock(RecordInterface::class);
        $record->method('getRawRecord')->willReturn($rawRecord);

        return $record;
    }

    /**
     * @param array<RecordInterface> $records
     */
    private function createCollection(array $records): LazyRecordCollection&MockObject
    {
        $collection = $this->createMock(LazyRecordCollection::class);
        $collection->method('getIterator')->willReturn(new \ArrayIterator($records));

        return $collection;
    }

    private function createSchemaFactory(bool $hasSchema): TcaSchemaFactory&MockObject
    {
        $schemaFactory = $this->createMock(TcaSchemaFactory::class);
        $schemaFactory->method('has')->willReturn($hasSchema);

        return $schemaFactory;
    }
}
