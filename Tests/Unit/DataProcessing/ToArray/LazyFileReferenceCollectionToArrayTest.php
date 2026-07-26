<?php

declare(strict_types=1);

namespace Netzbewegung\NbHeadlessContentBlocks\Tests\Unit\DataProcessing\ToArray;

use Netzbewegung\NbHeadlessContentBlocks\DataProcessing\ToArray\LazyFileReferenceCollectionToArray;
use TYPO3\CMS\Core\Resource\Collection\LazyFileReferenceCollection;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class LazyFileReferenceCollectionToArrayTest extends UnitTestCase
{
    public function testToArrayReturnsEmptyArrayForEmptyCollection(): void
    {
        $collection = new LazyFileReferenceCollection('test', fn() => []);
        $subject = new LazyFileReferenceCollectionToArray($collection);

        self::assertSame([], $subject->toArray());
    }
}
