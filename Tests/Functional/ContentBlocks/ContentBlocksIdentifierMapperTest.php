<?php

declare(strict_types=1);

namespace Netzbewegung\NbHeadlessContentBlocks\Tests\Functional\ContentBlocks;

use Netzbewegung\NbHeadlessContentBlocks\ContentBlocks\ContentBlocksIdentifierMapper;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class ContentBlocksIdentifierMapperTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = [
        'typo3conf/ext/nb_headless_content_blocks/Tests/Fixtures/Extensions/test_nb_headless_content_blocks',
        'typo3conf/ext/container',
        'typo3conf/ext/content_blocks',
        'typo3conf/ext/nb_headless_content_blocks',
    ];

    #[Test]
    public function mapsColumnToIdentifier(): void
    {
        $subject = $this->get(ContentBlocksIdentifierMapper::class);

        self::assertSame('my_text', $subject->mapColumnToIdentifier('tt_content', 'test_simple', 'test_simple_my_text'));
    }

    #[Test]
    public function returnsNullForUnknownTable(): void
    {
        $subject = $this->get(ContentBlocksIdentifierMapper::class);

        self::assertNull($subject->mapColumnToIdentifier('unknown_table', null, 'some_column'));
    }

    #[Test]
    public function returnsNullForUnknownColumn(): void
    {
        $subject = $this->get(ContentBlocksIdentifierMapper::class);

        self::assertNull($subject->mapColumnToIdentifier('tt_content', 'test_simple', 'not_a_registered_column'));
    }
}
