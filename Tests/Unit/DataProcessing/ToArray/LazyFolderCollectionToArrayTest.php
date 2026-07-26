<?php

declare(strict_types=1);

namespace Netzbewegung\NbHeadlessContentBlocks\Tests\Unit\DataProcessing\ToArray;

use Netzbewegung\NbHeadlessContentBlocks\DataProcessing\ToArray\LazyFolderCollectionToArray;
use TYPO3\CMS\Core\Resource\Collection\LazyFolderCollection;
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\Resource\ResourceStorage;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class LazyFolderCollectionToArrayTest extends UnitTestCase
{
    public function testToArrayBuildsCorrectPaths(): void
    {
        $storage = $this->createMock(ResourceStorage::class);
        $storage->method('getConfiguration')->willReturn(['basePath' => '/files/']);

        $folder1 = $this->createFolderMock($storage, '/documents/report.pdf');
        $folder2 = $this->createFolderMock($storage, '/images/photo.jpg');

        $collection = new LazyFolderCollection('test', fn() => [$folder1, $folder2]);
        $subject = new LazyFolderCollectionToArray($collection);

        self::assertSame([
            0 => '//files/documents/report.pdf',
            1 => '//files/images/photo.jpg',
        ], $subject->toArray());
    }

    public function testToArrayReturnsEmptyArrayForEmptyCollection(): void
    {
        $collection = new LazyFolderCollection('test', fn() => []);
        $subject = new LazyFolderCollectionToArray($collection);

        self::assertSame([], $subject->toArray());
    }

    private function createFolderMock(ResourceStorage $storage, string $identifier): Folder
    {
        $folder = $this->createMock(Folder::class);
        $folder->method('getStorage')->willReturn($storage);
        $folder->method('getIdentifier')->willReturn($identifier);

        return $folder;
    }
}
